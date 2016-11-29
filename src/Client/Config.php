<?php

namespace Jimdo\Notification\Event\Client;

class Config
{
    /** @var string */
    private $endpoint;

    /** @var string */
    private $stage;

    /** @var array */
    private $awsKey;

    /** @var string */
    private $awsSecret;

    /** @var string */
    private $awsRegion;

    /**
     * @param string $endpoint
     * @param string $stage
     * @param string $awsKey
     * @param string $awsSecret
     * @param string $awsRegion
     */
    public function __construct($endpoint, $stage, $awsKey, $awsSecret, $awsRegion)
    {
        $this->endpoint = $endpoint;
        $this->stage = $stage;
        $this->awsKey = $awsKey;
        $this->awsSecret = $awsSecret;
        $this->awsRegion = $awsRegion;
    }

    /**
     * @param string $function
     * @return string
     */
    public function endpoint($function = '')
    {
        $parts = [$this->endpoint, $this->stage];

        if (strlen($function) > 0) {
            $parts []= $function;
        }

        return join('/', $parts);
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
