<?php 

namespace Rf\Aws\Kinesis\ClientLibrary;

use Rf\Aws\Kinesis\ClientLibrary\Entity\KinesisShard;

interface KinesisShardDataStore
{
  public function modify(KinesisShard $shard);

  public function restore($stream_name);
}