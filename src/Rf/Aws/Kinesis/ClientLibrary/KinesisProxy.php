<?php 

namespace Rf\Aws\Kinesis\ClientLibrary;

use Aws\Kinesis\KinesisClient;
use Rf\Aws\Kinesis\ClientLibrary\Exception\KinesisProxyException;
use Rf\Aws\Kinesis\ClientLibrary\Entity\KinesisShard;
use Rf\Aws\Kinesis\ClientLibrary\Entity\KinesisDataRecord;
use Rf\Aws\Kinesis\ClientLibrary\KinesisShardDataStore;
use Rf\Aws\Kinesis\ClientLibrary\KinesisShardFileDataStore;

/**
* Wrapper library of Amazon Kinesis Client(http://docs.aws.amazon.com/aws-sdk-php/latest/class-Aws.Kinesis.KinesisClient.html)
*
* @license MIT License (MIT)
* @author FukuiReTu
*/
class KinesisProxy
{
  private static $instances = array();

  private $kinesis;

  private $stream_name;

  private $data_store;

  private $shard_hash;

  private $inited = false;

  private function __construct(KinesisClient $kinesis, KinesisShardDataStore $data_store, $stream_name, $init = true)
  {
    $this->kinesis = $kinesis;
    $this->data_store = $data_store;
    $this->stream_name = $stream_name;

    if ($init) {
      $this->initialize();
    }
  }

  public function initialize()
  {
    $this->shard_hash = $this->findWithMergeStoreShards();
    $this->inited = true;
  }

  public static function factory(KinesisClient $kinesis, KinesisShardDataStore $data_store, $stream_name, $init = true)
  {
    $key = sprintf("%s:%s:%s:%d", spl_object_hash($kinesis), spl_object_hash($data_store), $stream_name, $init);
    if (!isset(self::$instances[$key])) {
      $instance = new self($kinesis, $data_store, $stream_name, $init);
      self::$instances[$key] = $instance;
    } 

    return self::$instances[$key];
  }

  public function getKinesis()
  {
    return $this->kinesis;
  }

  public function getDataStore()
  {
    return $this->data_store;
  }

  public function findWithMergeStoreShards()
  {
    try {
      $origin_shards = $this->findOriginShards();

      $data_store = $this->getDataStore();
      $restored_shards = $data_store->restore($this->stream_name);

      foreach ($origin_shards as $origin_shard_id => $origin_shard) {
        foreach ($restored_shards as $restored_shard_id => $restored_shard) {
          if ($origin_shard->getShardId() === $restored_shard->getShardId()) {
            $origin_shard->setSequenceNumber($restored_shard->getSequenceNumber());
            break;
          }
        }
      }
    } catch (\Exception $e) {
      throw new KinesisProxyException($e->getMessage(), $e->getCode(), $e);
    }

    return $origin_shards;
  }

  public function findOriginShards()
  {
    $result = array();
    try {
      $kinesis = $this->getKinesis();
      while (true) {
        $describe_stream_result = $kinesis->describeStream(
          array(
            'StreamName' => $this->stream_name
          )
        );
        
        $stream_description = $describe_stream_result['StreamDescription'];
        $shards = $stream_description['Shards'];
        foreach ($shards as $shard) {
          $shard_id = $shard['ShardId'];

          $shard_obj = new KinesisShard();
          $shard_obj->setStreamName($this->stream_name)->setShardId($shard_id);

          $result[$shard_id] = $shard_obj;
        }

        // HasMoreShardsでまだshardがあるかチェック
        $has_more_shards = $stream_description['HasMoreShards'];
        if (!$has_more_shards) {
          break;
        }
      }
    } catch (\Exception $e) {
      throw new KinesisProxyException($e->getMessage(), $e->getCode(), $e);
    }

    return $result;
  }

  public function findDataRecords($target_shard_id = null, $limit = 1000, $max_loop_count = 5, $parallel = false)
  {
    if (!$this->inited) {
      throw new KinesisProxyException("Can not use initialize because not yet.");
    }

    if ($parallel && !extension_loaded('pthreads')) {
      throw new KinesisProxyException('pthreads is required');
    }

    try {
      $result = array();
      foreach ($this->shard_hash as $shard_id => $shard) {
        if (!is_null($target_shard_id)) {
          if ($target_shard_id !== $shard_id) {
            continue;
          }
        }

        if ($parallel) {
          throw new \RuntimeException('parallel is not implemented');
        } else {
          $data_records = $this->_findDataRecords($shard, $limit, $max_loop_count);
        }

        if (!empty($data_records)) {
          $end_data_record = end($data_records);
          $shard->setSequenceNumber($end_data_record->getSequenceNumber());
        } 

        $result = array_merge($result, $data_records);
      }
    } catch (\Exception $e) {
      throw new KinesisProxyException($e->getMessage(), $e->getCode(), $e);
    }

    return $result;
  }

  private function _findDataRecords(KinesisShard $shard, $limit, $max_loop_count)
  {
    $result = array();

    $option = null;
    if ($shard->getSequenceNumber() === '0') {
      $option = array('StreamName' => $shard->getStreamName(),
          'ShardId' => $shard->getShardId(),
          'ShardIteratorType' => 'TRIM_HORIZON'
      );
    } else {
      $option = array('StreamName' => $shard->getStreamName(),
        'ShardId' => $shard->getShardId(),
        'ShardIteratorType' => 'AFTER_SEQUENCE_NUMBER',
        'StartingSequenceNumber' => $shard->getSequenceNumber()
      );
    }

    $kinesis = $this->getKinesis();
    $shard_iterator_result = $kinesis->getShardIterator($option);

    $shard_iterator = $shard_iterator_result['ShardIterator'];
    for ($i = 0; $i < $max_loop_count ; $i++) { 
        $get_records_result = $kinesis->getRecords(array(
            'ShardIterator' => $shard_iterator,
            'Limit' => $limit
        ));

        $records = $get_records_result['Records'];
        foreach ($records as $record) {
          $data_record = new KinesisDataRecord();
          $data_record->setStreamName($shard->getStreamName())->setShardId($shard->getShardId())->setSequenceNumber($record['SequenceNumber'])
            ->setData($record['Data'])->setPartitionKey($record['PartitionKey']);

          $result[] = $data_record;
        }

        if (count($result)  >= $limit) {
            break;
        }

        $shard_iterator = $get_records_result['NextShardIterator'];
    }

    return $result;
  }

  public function checkpointAll()
  {
    foreach ($this->shard_hash as $shard_id => $shard) {
      $this->checkpoint($shard);
    }
  }

  public function checkpoint(KinesisShard $shard)
  {
    if (!$this->inited) {
      throw new KinesisProxyException("Can not use initialize because not yet.");
    }

    try {
      $data_store = $this->getDataStore();
      $data_store->modify($shard);
    } catch (\Exception $e) {
      throw new KinesisProxyException($e->getMessage(), $e->getCode(), $e);
    }
  }
}