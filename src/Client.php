<?php

namespace Jimdo\Notification\Event;

use Aws\Credentials\Credentials;
use Aws\Signature\SignatureInterface;

use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Client as HttpClient;

class Client
{
    /** @var Client\Config */
    private $config;

    /** @var \GuzzleHttp\ClientInterface */
    private $httpClient;

    /** @var \Aws\Signature\SignatureInterface $signature */
    private $signature;

    /**
     * @param Client\Config $config
     * @param \GuzzleHttp\ClientInterface $httpClient
     * @param \Aws\Signature\SignatureInterface $signature
     */
    public function __construct(Client\Config $config, \GuzzleHttp\ClientInterface $httpClient,  \Aws\Signature\SignatureInterface $signature)
    {
        $this->config = $config;
        $this->httpClient = $httpClient;
        $this->signature = $signature;
    }

    /**
     * @param string $endpoint
     * @param string $stage
     * @param string $awsKey
     * @param string $awsSecret
     * @param string $awsRegion
     * @param \GuzzleHttp\ClientInterface $httpClient
     * @return Client
     */
    public static function create($endpoint, $stage, $awsKey, $awsSecret, $awsRegion, $httpClient = null)
    {
        if ($httpClient === null) {
            $httpClient = new HttpClient();
        }

        return new self(
            new Client\Config($endpoint, $stage, $awsKey, $awsSecret, $awsRegion),
            $httpClient,
            new \Aws\Signature\SignatureV4('execute-api', $awsRegion)
        );
    }

    /**
     * @param string $function Path fragment to lambda function
     * @param array $arguments As in send() but without $function
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function __call($function, $arguments)
    {
        list($target, $id, $name, $payload) = $arguments;
        return $this->send($function, $target, $id, $name, $payload);
    }

    /**
     * @param string $function Path fragment to lambda function
     * @param mixed $target Target identifier (website id or group descriptor)
     * @param string $id Unique event identifier
     * @param string $name Event name
     * @param array $payload Arbitrary JSON payload
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function send($function, $target, $id, $name, array $payload)
    {
        return $this->httpClient->send(
            $this->signedRequest(
                $this->request(
                    $function,
                    $target,
                    $id,
                    $name,
                    $this->removeEmptyStringValues($payload)
                )
            )
        );
    }

    /**
     * @param string $function Path fragment to lambda function
     * @param mixed $target Target identifier (website id or group descriptor)
     * @param string $id Unique event identifier
     * @param string $name Event name
     * @param array $payload Arbitrary JSON payload
     * @return \GuzzleHttp\Psr7\Request
     */
    private function request($function, $target, $id, $name, array $payload)
    {
        $created = $this->timeInMilliseconds();

        $headers = [
            'X-Amz-Date' => date('c', $created),
            'Content-Type' => 'application/json',
        ];

        $body = json_encode([
            'id' => (string) $id,
            'name' => $name,
            $function => $target,
            'created' => $created,
            'payload' => $payload,
        ], JSON_FORCE_OBJECT);

        return new Request(
            'POST',
            $this->config->endpoint($function),
            $headers,
            $body
        );
    }

    /**
     * @param \GuzzleHttp\Psr7\Request $request Unsigned request
     * @return \GuzzleHttp\Psr7\Request Signed request
     */
    private function signedRequest(\GuzzleHttp\Psr7\Request $request)
    {
        return $this->signature->signRequest(
            $request,
            new Credentials($this->config->key(), $this->config->secret())
        );
    }

    /**
     * @param arary $payload
     * @return array Filtered payload
     */
    private function removeEmptyStringValues($payload) {
        return array_filter($payload, function ($value) {
            if ($value === '') {
                return false;
            }
            return true;
        });
    }

    /**
     * @return int
     */
    protected function timeInMilliseconds()
    {
        return (int)(microtime(true) * 1000);
    }
}
