<?php 

namespace Rf\Aws\Kinesis\ClientLibrary;

use Aws\Kinesis\KinesisClient;
use Aws\Common\Enum\Region;
use Rf\Aws\Kinesis\ClientLibrary\KinesisProxy;
use Rf\Aws\Kinesis\ClientLibrary\KinesisShardFileDataStore;
use Rf\Aws\Kinesis\ClientLibrary\Entity\KinesisShard;

class KinesisProxyTest extends \PHPUnit_Framework_TestCase
{
  public function setup()
  {
    require_once realpath(dirname(__FILE__) . '/../../') . '/bootstrap.php';
  }

  public function testFactory_001()
  {
    $dummy_kinesis = KinesisClient::factory(array(
      'key' => 'XXXXXX',
      'secret' => 'XXXXX',
      'region' => Region::VIRGINIA
    ));
    $data_store = new KinesisShardFileDataStore('/tmp/amazon-kinesis');
    $stream_name = 'dummy';

    $proxy1 = KinesisProxy::factory($dummy_kinesis, $data_store, $stream_name, false);
    $proxy2 = KinesisProxy::factory($dummy_kinesis, $data_store, $stream_name, false);
    $proxy3 = KinesisProxy::factory($dummy_kinesis, new KinesisShardFileDataStore('/tmp/amazon-kinesis'), $stream_name, false);
    $proxy4 = KinesisProxy::factory($dummy_kinesis, $data_store, 'hoge', false);

    $this->assertTrue(spl_object_hash($proxy1) === spl_object_hash($proxy2));
    $this->assertTrue(spl_object_hash($proxy1) === spl_object_hash($proxy3));
    $this->assertFalse(spl_object_hash($proxy1) === spl_object_hash($proxy4));
  }

  public function testFindMergeShards_001()
  {
    $shard = new KinesisShard();
    $shard->setStreamName('dummy-stream-name');
    $shard->setShardId('dummy-shardId-000000000000');
    $shard->setSequenceNumber('123456789');

    $data_store = $this->getMock("KinesisShardFileDataStore", array('restore'));
    $data_store->expects($this->any())
      ->method('restore')
      ->will($this->returnValue(array("dummy-shardId-000000000000" => $shard)));
    
    $proxy = $this->getMockBuilder('Rf\Aws\Kinesis\ClientLibrary\KinesisProxy')
    ->setMethods(array('getDataStore', 'findOriginShards'))
    ->disableOriginalConstructor()
    ->getMock();
    $proxy->expects($this->any())
        ->method('getDataStore')
        ->will($this->returnValue($data_store));
    
    $shard2 = new KinesisShard();
    $shard2->setStreamName('dummy-stream-name');
    $shard2->setShardId('dummy-shardId-000000000001');
    $shard2->setSequenceNumber('234567891');
    
    $proxy->expects($this->any())
        ->method('findOriginShards')
        ->will($this->returnValue(array("dummy-shardId-000000000001" => $shard2)));

    $result = $proxy->findWithMergeStoreShards();
    $this->assertCount(2, $result);
    $this->assertArrayHasKey('dummy-shardId-000000000000', $result);
    $this->assertArrayHasKey('dummy-shardId-000000000001', $result);
    $result_shard = $result['dummy-shardId-000000000000'];
    $this->assertEquals($shard->getStreamName(), $result_shard->getStreamName());
    $this->assertEquals($shard->getShardId(), $result_shard->getShardId());
    $this->assertEquals($shard->getSequenceNumber(), $result_shard->getSequenceNumber());
    $result_shard2 = $result['dummy-shardId-000000000001'];
    $this->assertEquals($shard2->getStreamName(), $result_shard2->getStreamName());
    $this->assertEquals($shard2->getShardId(), $result_shard2->getShardId());
    $this->assertEquals($shard2->getSequenceNumber(), $result_shard2->getSequenceNumber());
  }

  public function testFindWithMergeStoreShards_002()
  {
    $data_store = $this->getMock("KinesisShardFileDataStore", array('restore'));
    $data_store->expects($this->any())
      ->method('restore')
      ->will($this->returnValue(array()));

    $proxy = $this->getMockBuilder('Rf\Aws\Kinesis\ClientLibrary\KinesisProxy')
    ->setMethods(array('getDataStore', 'findOriginShards'))
    ->disableOriginalConstructor()
    ->getMock();
    $proxy->expects($this->any())
        ->method('getDataStore')
        ->will($this->returnValue($data_store));
    
    $shard = new KinesisShard();
    $shard->setStreamName('dummy-stream-name');
    $shard->setShardId('dummy-shardId-000000000001');
    $shard->setSequenceNumber('234567891');
    $proxy->expects($this->any())
        ->method('findOriginShards')
        ->will($this->returnValue(array("dummy-shardId-000000000001" => $shard)));

    $result = $proxy->findWithMergeStoreShards();
    $this->assertCount(1, $result);
    $result_shard = $result['dummy-shardId-000000000001'];
    $this->assertEquals($shard->getStreamName(), $result_shard->getStreamName());
    $this->assertEquals($shard->getShardId(), $result_shard->getShardId());
    $this->assertEquals($shard->getSequenceNumber(), $result_shard->getSequenceNumber());
  }

  public function testFindOriginShards_001()
  {
    $kinesis = $this->getMock("KinesisClient", array('describeStream'));
    $stream_description = array('StreamDescription' => array(
      'HasMoreShards' => false,
      'Shards' => array(
        array(
          'ShardId' => 'dummy-shardId-000000000000',
          'SequenceNumberRange' => array(
            'StartingSequenceNumber' => '123456789'
          )
        )
      )
    ));
    $kinesis->expects($this->any())
             ->method('describeStream')
             ->will($this->returnValue($stream_description));

    $proxy = $this->getMockBuilder('Rf\Aws\Kinesis\ClientLibrary\KinesisProxy')
      ->setMethods(array('getKinesis'))
      ->disableOriginalConstructor()
      ->getMock();
    $proxy->expects($this->any())
        ->method('getKinesis')
        ->will($this->returnValue($kinesis));

    $result = $proxy->findOriginShards();
    $this->assertCount(1, $result);
    $this->assertArrayHasKey('dummy-shardId-000000000000', $result);
    $shard = $result['dummy-shardId-000000000000'];
    $this->assertNull($shard->getStreamName());
    $this->assertEquals('dummy-shardId-000000000000', $shard->getShardId());
    $this->assertEquals('123456789', $shard->getSequenceNumber());
  }

  public function testFindDataRecords_001()
  {
    $data_store = $this->getMock("KinesisShardFileDataStore", array('restore'));
    $data_store->expects($this->any())
      ->method('restore')
      ->will($this->returnValue(array()));

    $kinesis = $this->getMock("KinesisClient", array('getShardIterator', 'getRecords'));
    $kinesis->expects($this->any())
             ->method('getShardIterator')
             ->will($this->returnValue(array('ShardIterator' => '123456789')));
    $kinesis->expects($this->any())
             ->method('getRecords')
             ->will($this->returnValue(array('NextShardIterator' =>'234567891', 'Records' => array(
                    array('SequenceNumber' =>'11111', 'Data' => 'hoge', 'PartitionKey' => 'pt1'),
                    array('SequenceNumber' =>'11112', 'Data' => 'foo', 'PartitionKey' => 'pt1')
    ))));

    $proxy = $this->getMockBuilder('Rf\Aws\Kinesis\ClientLibrary\KinesisProxy')
    ->setMethods(array('getKinesis', 'getDataStore', 'findOriginShards'))
    ->disableOriginalConstructor()
    ->getMock();
    $proxy->expects($this->any())
        ->method('getKinesis')
        ->will($this->returnValue($kinesis));
    $proxy->expects($this->any())
        ->method('getDataStore')
        ->will($this->returnValue($data_store));
    
    $shard = new KinesisShard();
    $shard->setStreamName('dummy-stream-name');
    $shard->setShardId('dummy-shardId-000000000001');
    $shard->setSequenceNumber('234567891');
    $proxy->expects($this->any())
        ->method('findOriginShards')
        ->will($this->returnValue(array("dummy-shardId-000000000001" => $shard)));

    $proxy->initialize();
    $result = $proxy->findDataRecords(null, 1000, 1);
    $this->assertCount(2, $result);
    $result_data_record1 = $result[0];
    $this->assertEquals('dummy-stream-name', $result_data_record1->getStreamName());
    $this->assertEquals('dummy-shardId-000000000001', $result_data_record1->getShardId());
    $this->assertEquals('11111', $result_data_record1->getSequenceNumber());
    $this->assertEquals('hoge', $result_data_record1->getData());
    $this->assertEquals('pt1', $result_data_record1->getPartitionKey());
    $result_data_record2 = $result[1];
    $this->assertEquals('11112', $result_data_record2->getSequenceNumber());
    $this->assertEquals('foo', $result_data_record2->getData());
    $this->assertEquals('pt1', $result_data_record2->getPartitionKey());
  }

  public function testCheckpoint_001()
  {
    $store_dir = '/tmp/amazon-kinesis/dummy-stream-name';
    if (file_exists($store_dir)) {
      exec("rm -r $store_dir");
    }

    $proxy = $this->getMockBuilder('Rf\Aws\Kinesis\ClientLibrary\KinesisProxy')
      ->setMethods(array('getDataStore', 'findWithMergeStoreShards'))
      ->disableOriginalConstructor()
      ->getMock();

    $proxy->expects($this->any())
      ->method('getDataStore')
      ->will($this->returnValue(new KinesisShardFileDataStore('/tmp/amazon-kinesis')));
    $proxy->expects($this->any())
      ->method('findWithMergeStoreShards')
      ->will($this->returnValue(array()));

    $shard = new KinesisShard();
    $shard->setStreamName('dummy-stream-name');
    $shard->setShardId('dummy-shardId-000000000001');
    $shard->setSequenceNumber('234567891');

    $proxy->initialize();
    $proxy->checkpoint($shard);

    $this->assertTrue(file_exists($store_dir . '/dummy-shardId-000000000001'));
    $file_handle = fopen($store_dir . '/dummy-shardId-000000000001', "r" );
    list($stream_name, $shard_id, $sequence_number) = fgetcsv($file_handle);
    $this->assertEquals($shard->getStreamName(), $stream_name);
    $this->assertEquals($shard->getShardId(), $shard_id);
    $this->assertEquals($shard->getSequenceNumber(), $sequence_number);
  }
}