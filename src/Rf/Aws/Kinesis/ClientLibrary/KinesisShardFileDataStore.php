<?php 

namespace Rf\Aws\Kinesis\ClientLibrary;

use Rf\Aws\Kinesis\ClientLibrary\KinesisShardDataStore;
use Rf\Aws\Kinesis\ClientLibrary\Entity\KinesisShard;

class KinesisShardFileDataStore implements KinesisShardDataStore
{
  const DATA_STORE_FILE_DIR = '/tmp/amazon-kinesis';

  public function modify(KinesisShard $shard)
  {
      $store_dir = static::DATA_STORE_FILE_DIR . '/' . $shard->getStreamName();
      if (!file_exists($store_dir)) {
        mkdir($store_dir, 0755, true);
      }

      $file_name = $store_dir . '/' . $shard->getShardId();
      if ($file_handle = @fopen($file_name, 'x')) {
        fclose ($file_handle);
      }

      $file_handle = fopen($file_name , "rb+" ); 
      $flag = flock($file_handle, LOCK_SH);

      fwrite($file_handle, implode(',', array($shard->getStreamName(), $shard->getShardId(), $shard->getSequenceNumber())));
      fclose($file_handle);
  }

  public function restore($target_stream_name)
  {
    $result = array();

    $store_dir = static::DATA_STORE_FILE_DIR . '/' . $target_stream_name;
    if (!file_exists($store_dir)) {
      return $result;
    }

    if ($dir_handle = opendir($store_dir)) {
      while (false !== ($file = readdir($dir_handle))) {
          if (strpos($file, $target_stream_name, 0) !== 0) {
            // 対象のストリームのみ絞り込む
            continue;
          }

          $file_handle = fopen($store_dir . '/' . $file, "r" );
          while ($shard_info = fgetcsv($file_handle)) {
            list($stream_name, $shard_id, $sequence_number) = $shard_info;
            $shard = new KinesisShard();
            $shard->setStreamName($stream_name);
            $shard->setShardId($shard_id);
            $shard->setSequenceNumber($sequence_number);

            $result[$shard_id] = $shard;
          }
          fclose($file_handle);
      }
      closedir($dir_handle);
    }

    return $result;
  }
}