<?php 

namespace Rf\Aws\Kinesis\ClientLibrary;

use Rf\Aws\Kinesis\ClientLibrary\KinesisShardDataStore;
use Rf\Aws\Kinesis\ClientLibrary\Entity\KinesisShard;

/**
* Manipulating the storage to manage the Shard(memcached)
* 
* @license MIT License (MIT)
* @author FukuiReTu
*/
class KinesisShardMemcacheDataStore implements KinesisShardDataStore
{
  private $memcache;

  public function __construct(\Memcache $memcache)
  {
    $this->memcache = $memcache;
  }

  /**
   * @Override
   */
  public function modify(KinesisShard $shard)
  {
    $key = sprintf("%s:%s", $shard->getStreamName(), $shard->getShardId());
    $this->memcache->set($key, $shard->getSequenceNumber());
  }

  /**
   * @Override
   */
  public function restore($target_stream_name)
  {
    $result = array();

    $keys = $this->_getKeys($target_stream_name);
    foreach ($keys as $key) {
      list($stream_name, $shard_id) = explode(':', $key);
      $sequence_number = $this->memcache->get($key);

      $shard = new KinesisShard();
      $shard->setStreamName($stream_name)->setShardId($shard_id)->setSequenceNumber($sequence_number);

      $result[] = $shard;
    }

    return $result;
  }

  private function _getKeys($target_stream_name)
  {
    $result = array();

    $items = $this->memcache->getStats( "items" );
    if(isset($items['items'])) {
      foreach ($items['items'] as $slabId => $item) {
        $cachedump = $this->memcache->getStats( 'cachedump', $slabId, $item['number'] );
        foreach ($cachedump as $key => $val) {
          if (strpos($key, $target_stream_name, 0) === 0) {
            $result[] = $key;
          }
        }
      }
    }

    return $result;
  }
}