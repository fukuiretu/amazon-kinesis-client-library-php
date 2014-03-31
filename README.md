amazon-kinesis-client-library-php 
=================================
[![Build Status](https://travis-ci.org/fukuiretu/amazon-kinesis-client-library-php.svg?branch=0.0.1)](https://travis-ci.org/fukuiretu/amazon-kinesis-client-library-php)

# Overview

Wrapper library of Amazon Kinesis Client Module.(http://docs.aws.amazon.com/aws-sdk-php/latest/class-Aws.Kinesis.KinesisClient.html)

I want to simplify the management of Shard Id and Sequence Number taken out from the Stream. In addition, storage to manage is also an option.


# Requirements
- PHP >= 5.3.3
- aws-sdk-php (https://github.com/aws/aws-sdk-php)
- [option] memcache.so (http://pecl.php.net/package/memcache) AND memcached (http://memcached.org/)

# Installation

### Using Composer

composer.json

````
{
    "require": {
        "rf/amazon-kinesis-client-library-php": "0.0.1"
    }
}
````

### Copy Directory

````
git clone https://github.com/fukuiretu/amazon-kinesis-client-library-php.git
cp -r src/Rf <path/to/your_project>
````

# Usage
### CASE: Memache the Data Store

````
<?php 

require 'vendor/autoload.php';

use Aws\Kinesis\KinesisClient;
use Aws\Common\Enum\Region;
use Rf\Aws\AutoLoader;
use Rf\Aws\Kinesis\ClientLibrary\KinesisProxy;
use Rf\Aws\Kinesis\ClientLibrary\KinesisShard;
use Rf\Aws\Kinesis\ClientLibrary\KinesisShardFileDataStore;
use Rf\Aws\Kinesis\ClientLibrary\KinesisShardMemcacheDataStore;

define('STREAM_NAME', 'kinesis-trial');
$kinesis = KinesisClient::factory(array(
  'key' => 'XXXXX',
  'secret' => 'XXXXX',
  'region' => Region::VIRGINIA
));

$memcache = new Memcache();
$memcache->addServer("localhost", 11211);

$kinesis_proxy = KinesisProxy::factory($kinesis, new KinesisShardMemcacheDataStore($memcache), STREAM_NAME);
$data_records = $kinesis_proxy->findDataRecords();

foreach ($data_records as $data_record) {
  echo $data_record->getData(), PHP_EOL;
}

$kinesis_proxy->checkpointAll();

````

### CASE: File the Data Store

````

<?php 

require 'vendor/autoload.php';

use Aws\Kinesis\KinesisClient;
use Aws\Common\Enum\Region;
use Rf\Aws\AutoLoader;
use Rf\Aws\Kinesis\ClientLibrary\KinesisProxy;
use Rf\Aws\Kinesis\ClientLibrary\KinesisShard;
use Rf\Aws\Kinesis\ClientLibrary\KinesisShardFileDataStore;
use Rf\Aws\Kinesis\ClientLibrary\KinesisShardMemcacheDataStore;

define('STREAM_NAME', 'kinesis-trial');

$kinesis = KinesisClient::factory(array(
  'key' => 'XXXXX',
  'secret' => 'XXXXX',
  'region' => Region::VIRGINIA
));

$kinesis_proxy = KinesisProxy::factory($kinesis, new KinesisShardFileDataStore('/tmp/amazon-kinesis'), STREAM_NAME);
$data_records = $kinesis_proxy->findDataRecords();

foreach ($data_records as $data_record) {
  echo $data_record->getData(), PHP_EOL;
}

$kinesis_proxy->checkpointAll();

````

### The following is required if you do not use the Composer

````
require 'src/Rf/Aws/AutoLoader.php';
AutoLoader::register();
````

### Specify ShardId, the acquisition number
The following is an example to get the DataRecord of 10,000 (1000 * 10) matter at most from Shard of "shardId-000000000000"
````
$data_records = $kinesis_proxy->findDataRecords('shardId-000000000000', 1000, 10);
````

# Todos
- Support the increase of storage. DynamoDB, Redis And More ...
- Parallel processing.

# Copyright
Copyright:: Copyright (c) 2014- Fukui ReTu License:: MIT