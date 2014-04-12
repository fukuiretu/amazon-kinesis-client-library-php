<?php 

namespace Rf\Aws\Kinesis\ClientLibrary;

/**
*
* @license MIT License (MIT)
* @author FukuiReTu
*/
class KinesisStorageManager
{
  private $kinesis_proxy;

  private $data_store;

  private $shards;

  public function __construct($kinesis_proxy, $data_store)
  {
    $this->kinesis_proxy = $kinesis_proxy;
    $this->data_store = $data_store;
  }

  public function findWithMergeStoreShards()
  {
    $origin_shards = $this->kinesis_proxy->findShards();
    $restored_shards = $this->$data_store->restore($this->$kinesis_proxy->getStreamName());

    foreach ($origin_shards as $origin_shard_id => $origin_shard) {
      foreach ($restored_shards as $restored_shard_id => $restored_shard) {
        if ($origin_shard->getShardId() === $restored_shard->getShardId()) {
          $origin_shard->setSequenceNumber($restored_shard->getSequenceNumber());
          break;
        }
      }
    }

    $this->$shards = $origin_shards;

    return $origin_shards;
  }

  public function findWithMergeStoreDataRecords($target_shard_id = null, $limit = 1000, $max_loop_count = 5, $parallel = false)
  {
    if ($parallel && !extension_loaded('pthreads')) {
      throw new KinesisProxyException('pthreads is required');
    }

    $result = array();
    $shards = $this->findWithMergeStoreShards();
    foreach ($shards as $shard_id => $shard) {
      if (!is_null($target_shard_id)) {
        if ($target_shard_id !== $shard_id) {
          continue;
        }
      }

      if ($parallel) {
        throw new \RuntimeException('parallel is not implemented');
      } else {
        $data_records = $this->kinesis_proxy->findDataRecords($shard, $limit, $max_loop_count);
      }

      if (!empty($data_records)) {
        $end_data_record = end($data_records);
        $shard->setSequenceNumber($end_data_record->getSequenceNumber());
      } 

      $result = array_merge($result, $data_records);
    }

    return $result;
  }

  public function checkpointAll()
  {
    if (is_null($this->shards)) {
      throw new KinesisProxyException("Can not use initialize because not yet.");
    }

    foreach ($this->shards as $shard_id => $shard) {
      $this->checkpoint($shard);
    }
  }

  public function checkpoint(KinesisShard $shard)
  {
    try {
      $data_store = $this->getDataStore();
      $data_store->modify($shard);
    } catch (\Exception $e) {
      throw new KinesisProxyException($e->getMessage(), $e->getCode(), $e);
    }
  }
}