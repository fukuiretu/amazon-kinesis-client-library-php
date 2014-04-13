<?php 

namespace Rf\Aws\Kinesis\ClientLibrary;

use Aws\Kinesis\KinesisClient;
use Rf\Aws\Kinesis\ClientLibrary\Exception\KinesisProxyException;
use Rf\Aws\Kinesis\ClientLibrary\Entity\KinesisShard;
use Rf\Aws\Kinesis\ClientLibrary\Entity\KinesisDataRecord;

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

  private function __construct(KinesisClient $kinesis, $stream_name)
  {
    $this->kinesis = $kinesis;
    $this->stream_name = $stream_name;
  }

  public function getStreamName()
  {
    return $this->stream_name;
  }

  public static function factory(KinesisClient $kinesis, $stream_name)
  {
    $key = sprintf("%s:%s", spl_object_hash($kinesis), $stream_name);
    if (!isset(self::$instances[$key])) {
      $instance = new self($kinesis, $stream_name);
      self::$instances[$key] = $instance;
    } 

    return self::$instances[$key];
  }

  public function getKinesis()
  {
    return $this->kinesis;
  }

  public function findShards()
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
          $shard_obj->setStreamName($this->stream_name)
            ->setShardId($shard_id);

          $result[$shard_id] = $shard_obj;
        }

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

  public function findDataRecords(KinesisShard $shard, $limit, $max_loop_count)
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
          $data_record->setStreamName($shard->getStreamName())
            ->setShardId($shard->getShardId())
            ->setSequenceNumber($record['SequenceNumber'])
            ->setData($record['Data'])
            ->setPartitionKey($record['PartitionKey']);

          $result[] = $data_record;
        }

        if (count($result)  >= $limit) {
            break;
        }

        $shard_iterator = $get_records_result['NextShardIterator'];
    }

    return $result;
  }

  public function putRecord($data, $partitionKey)
  {
    try {
      $result = $this->kinesis->putRecord(array(
          'StreamName' => $this->stream_name,
          'Data' => $data,
          'PartitionKey' => $partitionKey
      ));
    } catch (\Exception $e) {
      throw new KinesisProxyException($e->getMessage(), $e->getCode(), $e);
    }

    return $result;
  }
}