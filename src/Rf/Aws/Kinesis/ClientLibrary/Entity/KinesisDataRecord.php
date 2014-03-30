<?php 

namespace Rf\Aws\Kinesis\ClientLibrary\Entity;

/**
* The entity class for DataRecord
* 
* @license MIT License (MIT)
* @author FukuiReTu
*/
class KinesisDataRecord
{
    private $stream_name;

    private $shard_id;

    private $sequence_number = '0';

    private $data;

    private $partition_key;

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

    /**
     * Gets the value of data.
     *
     * @return mixed
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * Sets the value of data.
     *
     * @param mixed $data the data
     *
     * @return self
     */
    public function setData($data)
    {
        $this->data = $data;

        return $this;
    }

    /**
     * Gets the value of partition_key.
     *
     * @return mixed
     */
    public function getPartitionKey()
    {
        return $this->partition_key;
    }

    /**
     * Sets the value of partition_key.
     *
     * @param mixed $partition_key the partition_key
     *
     * @return self
     */
    public function setPartitionKey($partition_key)
    {
        $this->partition_key = $partition_key;

        return $this;
    }
}