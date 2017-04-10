<?php

namespace Jimdo\Notification\Event;

use Aws\Credentials\Credentials;
use Aws\Sqs\SqsClient;

use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Client as HttpClient;

class Client
{
    const SQS_API_VERSION = '2012-11-05';

    /** @var Client\Config */
    private $config;

    /** @var \Aws\Sqs\SqsClient */
    private $sqsClient;

    /**
     * @param Client\Config $config
     * @param \Aws\Sqs\SqsClient $sqsClient
     */
    public function __construct(Client\Config $config, \Aws\Sqs\SqsClient $sqsClient)
    {
        $this->config = $config;
        $this->sqsClient = $sqsClient;
    }

    /**
     * @param string $queueUrl
     * @param string $awsKey
     * @param string $awsSecret
     * @param string $awsRegion
     * @param \Aws\Sqs\SqsClient $sqsClient
     * @return Client
     */
    public static function create($queueUrl, $awsKey, $awsSecret, $awsRegion, $sqsClient = null)
    {
        if ($sqsClient === null) {
            $sqsClient = new SqsClient([
                'credentials' => new Credentials($awsKey, $awsSecret),
                'region' => $awsRegion,
                'version' => self::SQS_API_VERSION,
            ]);
        }

        return new self(
            new Client\Config($queueUrl, $awsKey, $awsSecret, $awsRegion),
            $sqsClient
        );
    }

    /**
     * @param string $target Target of the notification
     * @param array $arguments As in send() but without $target
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function __call($target, $arguments)
    {
        list($targetId, $id, $name, $payload) = $arguments;
        return $this->send($target, $targetId, $id, $name, $payload);
    }

    /**
     * @param string $target Target of the notification
     * @param mixed $targetId Target identifier (website id or group descriptor)
     * @param string $id Unique event identifier
     * @param string $name Event name
     * @param array $payload Arbitrary JSON payload
     * @return \Aws\Result
     */
    public function send($target, $targetId, $id, $name, array $payload)
    {
        return $this->sqsClient->sendMessage([
            'QueueUrl' => $this->config->queueUrl(),
            'MessageBody' => $this->messageBody(
                $target,
                $targetId,
                $id,
                $name,
                $this->removeEmptyKeys($payload)
            ),
        ]);
    }

    /**
     * @param string $target Path fragment to lambda function
     * @param mixed $targetId Target identifier (website id or group descriptor)
     * @param string $id Unique event identifier
     * @param string $name Event name
     * @param array $payload Arbitrary JSON payload
     * @return \GuzzleHttp\Psr7\Request
     */
    private function messageBody($target, $targetId, $id, $name, array $payload)
    {
        return json_encode([
            'id' => (string) $id,
            'name' => $name,
            $target => $targetId,
            'created' => $this->timeInMilliseconds(),
            'payload' => empty($payload) ? (object)[] : $payload,
        ]);
    }

    /**
     * @param arary $array
     * @return array Filtered array
     */
    private function removeEmptyKeys($array) {
        $callback = function($item) use (&$callback) {
            if (is_array($item)) {
                return array_filter($item, $callback);
            }

            return is_numeric($item) || !empty($item);
        };

        return array_filter($array, $callback);
    }

    /**
     * @return int
     */
    protected function timeInMilliseconds()
    {
        return (int)(microtime(true) * 1000);
    }
}
