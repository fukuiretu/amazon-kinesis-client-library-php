<?php
require '../vendor/autoload.php';

use Aws\Kinesis\KinesisClient;
use Aws\Common\Enum\Region;
use Rf\Aws\Kinesis\ClientLibrary\KinesisProxy;

define('STREAM_NAME', 'kinesis-trial');

$kinesis = KinesisClient::factory(array(
  'key' => 'XXXXX',
  'secret' => 'XXXXX',
  'region' => Region::VIRGINIA
));

$kinesis_proxy = KinesisProxy::factory($kinesis, STREAM_NAME);

while (true) {
    $sample_data = date('YmdHis');
    $kinesis_proxy->putRecord($sample_data, mt_rand(1, 1000000));

    echo $sample_data, PHP_EOL;
    sleep(1);
}