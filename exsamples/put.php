<?php
require '../vendor/autoload.php';

use Aws\Kinesis\KinesisClient;
use Aws\Common\Enum\Region;

$kinesis = KinesisClient::factory(array(
  'key' => 'XXXXX',
  'secret' => 'XXXXX',
  'region' => Region::VIRGINIA
));

while (true) {
    $sample_data = date('YmdHis');

    $result = $kinesis->putRecord(array(
        // StreamName is required
        'StreamName' => 'kinesis-trial',
        // Data is required
        'Data' => $sample_data,
        // PartitionKey is required
        'PartitionKey' => mt_rand(1, 1000000),
        // 'ExplicitHashKey' => 'string',
        // 'SequenceNumberForOrdering' => '1'
    ));
    echo $sample_data, PHP_EOL;
    sleep(1);
}