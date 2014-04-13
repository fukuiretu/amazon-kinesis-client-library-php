<?php 

namespace Rf\Aws\Kinesis\ClientLibrary;

use Rf\Aws\Kinesis\ClientLibrary\KinesisProxy;
use Rf\Aws\Kinesis\ClientLibrary\KinesisShardDataStore;

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

  public function __construct(KinesisProxy $kinesis_proxy, KinesisShardDataStore $data_store)
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

    return $origin_shards;
  }

  public function findWithMergeStoreDataRecords($target_shard_id = null, $limit = 1000, $max_loop_count = 5, $parallel = false)
  {
    $result = array();
    $shards = $this->findWithMergeStoreShards();
    foreach ($shards as $shard_id => $shard) {
      if (!is_null($target_shard_id)) {
        if ($target_shard_id !== $shard_id) {
          continue;
        }
      }

      if ($parallel) {
        throw new \RuntimeException('parallel is not implemented. Sorry...');
      } else {
        $data_records = $this->kinesis_proxy->findDataRecords($shard, $limit, $max_loop_count);
      }

      if (!empty($data_records)) {
        $end_data_record = end($data_records);
        $shard->setSequenceNumber($end_data_record->getSequenceNumber());
      } 

      $result = array_merge($result, $data_records);
    }

    $this->$shards = $origin_shards;

    return $result;
  }

  public function saveAll()
  {
    if (is_null($this->shards)) {
      throw new \RuntimeException("Can not use initialize because not yet.");
    }

    foreach ($this->shards as $shard_id => $shard) {
      $this->save($shard);
    }
  }

  public function save(KinesisShard $shard)
  {
      $data_store = $this->$data_store;
      $data_store->modify($shard);
  }
}