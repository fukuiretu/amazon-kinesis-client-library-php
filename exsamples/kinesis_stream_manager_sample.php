<?php 

require '../vendor/autoload.php';
require '../src/Aws/AutoLoader.php';

use Aws\AutoLoader;
use Aws\Kinesis\KinesisClient;
use Aws\Common\Enum\Region;
use Aws\Kinesis\ClientLibrary\KinesisProxy;
use Aws\Kinesis\ClientLibrary\KinesisShard;
use Aws\Kinesis\ClientLibrary\KinesisShardFileDataStore;

define('STREAM_NAME', 'kinesis-trial');

AutoLoader::register();

$kinesis = KinesisClient::factory(array(
  'key' => 'XXXXX',
  'secret' => 'XXXXX',
  'region' => Region::VIRGINIA
));

$kinesis_proxy = KinesisProxy::factory($kinesis, new KinesisShardFileDataStore('/tmp/amazon-kinesis'), STREAM_NAME);
$data_records = $kinesis_proxy->findDataRecords();

foreach ($data_records as $data_record) {
  var_dump($data_record);
}

$kinesis_proxy->checkpointAll();