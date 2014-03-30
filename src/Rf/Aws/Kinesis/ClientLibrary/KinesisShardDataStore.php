<?php 

namespace Rf\Aws\Kinesis\ClientLibrary;

use Rf\Aws\Kinesis\ClientLibrary\Entity\KinesisShard;

/**
* Manipulating the storage to manage the Shard
* 
* @license MIT License (MIT)
* @author FukuiReTu
*/
interface KinesisShardDataStore
{
  public function modify(KinesisShard $shard);

  public function restore($stream_name);
}