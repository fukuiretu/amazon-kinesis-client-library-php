<?php 

namespace Rf\Aws\Kinesis\ClientLibrary\Entity;

/**
* The entity class for Shard
* 
* @license MIT License (MIT)
* @author FukuiReTu
*/
class KinesisShard
{
    private $stream_name;

    private $shard_id;

    private $sequence_number = '0';

  /**
     * Gets the value of shard_id.
     *
     * @return mixed
     */
    public function getStreamName()
    {
        return $this->stream_name;
    }

    /**
     * Sets the value of shard_id.
     *
     * @param mixed $shard_id the shard_id
     *
     * @return self
     */
    public function setStreamName($stream_name)
    {
        $this->stream_name = $stream_name;

        return $this;
    }
    /**
     * Gets the value of shard_id.
     *
     * @return mixed
     */
    public function getShardId()
    {
        return $this->shard_id;
    }

    /**
     * Sets the value of shard_id.
     *
     * @param mixed $shard_id the shard_id
     *
     * @return self
     */
    public function setShardId($shard_id)
    {
        $this->shard_id = $shard_id;

        return $this;
    }

    /**
     * Gets the value of sequence_number.
     *
     * @return mixed
     */
    public function getSequenceNumber()
    {
        return $this->sequence_number;
    }

    /**
     * Sets the value of sequence_number.
     *
     * @param mixed $sequence_number the sequence_number
     *
     * @return self
     */
    public function setSequenceNumber($sequence_number)
    {
        $this->sequence_number = $sequence_number;

        return $this;
    }
}