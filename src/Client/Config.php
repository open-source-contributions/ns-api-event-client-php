<?php

namespace Jimdo\Notification\Event\Client;

class Config
{
    /** @var string */
    private $queueUrl;

    /** @var array */
    private $awsKey;

    /** @var string */
    private $awsSecret;

    /** @var string */
    private $awsRegion;

    /**
     * @param string $queueUrl
     * @param string $awsKey
     * @param string $awsSecret
     * @param string $awsRegion
     */
    public function __construct($queueUrl, $awsKey, $awsSecret, $awsRegion)
    {
        $this->queueUrl = $queueUrl;
        $this->awsKey = $awsKey;
        $this->awsSecret = $awsSecret;
        $this->awsRegion = $awsRegion;
    }

    /**
     * @return string
     */
    public function queueUrl()
    {
        return $this->queueUrl;
    }

    /**
     * @return string AWS key
     */
    public function key()
    {
        return $this->awsKey;
    }

    /**
     * @return string AWS secret
     */
    public function secret()
    {
        return $this->awsSecret;
    }

    /**
     * @return string AWS region
     */
    public function region()
    {
        return $this->awsRegion;
    }
}
