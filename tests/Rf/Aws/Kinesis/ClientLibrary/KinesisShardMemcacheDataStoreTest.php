<?php 

namespace Rf\Aws\Kinesis\ClientLibrary;

use Aws\Kinesis\KinesisClient;
use Aws\Common\Enum\Region;
use Rf\Aws\Kinesis\ClientLibrary\KinesisProxy;
use Rf\Aws\Kinesis\ClientLibrary\KinesisShardFileDataStore;
use Rf\Aws\Kinesis\ClientLibrary\Entity\KinesisShard;

class KinesisShardMemcacheDataStoreTest extends \PHPUnit_Framework_TestCase
{
  private $memcache;

  public function setup()
  {
    require_once realpath(dirname(__FILE__) . '/../../') . '/bootstrap.php';

    $this->memcache = new \Memcache();
    $this->memcache->addServer('localhost', 11211);
    $this->memcache->flush();
  }

  public function testModify_001()
  {
    $shard = $this->_storeShard('dummy-stream-name', 'dummy-shardId-000000000000', '123456789');

    $key = sprintf("%s:%s", $shard->getStreamName(), $shard->getShardId());
    $sequence_number =$this->memcache->get($key);

    $this->assertEquals($shard->getSequenceNumber(), $sequence_number);
  }

  public function testRestore_001()
  {
    $shard = $this->_storeShard('dummy-stream-name', 'dummy-shardId-000000000000', '123456789');

    $data_store = new KinesisShardMemcacheDataStore($this->memcache);
    $stored_shards = $data_store->restore('dummy-stream-name');

    $this->assertEquals($shard->getStreamName(), $stored_shards[0]->getStreamName());
    $this->assertEquals($shard->getShardId(), $stored_shards[0]->getShardId());
    $this->assertEquals($shard->getSequenceNumber(), $stored_shards[0]->getSequenceNumber());
  }

  private function _storeShard($stream_name, $shard_id, $sequence_number)
  {
    $shard = new KinesisShard();
    $shard->setStreamName($stream_name)->setShardId($shard_id)->setSequenceNumber($sequence_number);

    $data_store = new KinesisShardMemcacheDataStore($this->memcache);
    $data_store->modify($shard);

    return $shard;
  }
}