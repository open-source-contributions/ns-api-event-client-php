<?php

namespace Jimdo\Notification\Event;

use PHPUnit\Framework\TestCase;

use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;

use Aws\Credentials\Credentials;
use Aws\Sqs\SqsClient;

class ClientTest extends TestCase
{
    private $requests;

    private $cannedResponseForAllSqsRequests = '<?xml version="1.0"?><SendMessageResponse xmlns="http://queue.amazonaws.com/doc/2012-11-05/"><SendMessageResult><MessageId>95809e0e-2cf7-44cc-bf44-1d9ffbe7ec3c</MessageId><MD5OfMessageBody>961770dca2cb59402568f975513228fe</MD5OfMessageBody></SendMessageResult><ResponseMetadata><RequestId>19293607-af0f-596d-806d-6744cd17de91</RequestId></ResponseMetadata></SendMessageResponse>';

    protected function setUp()
    {
        $this->requests = [];
    }

    /**
     * @test
     */
    public function itShouldRemoveEmptyStringValuesFromPayload()
    {
        $created = (int)(microtime(true) * 1000);

        $client = $this->client(['timeInMilliseconds']);
        $client->method('timeInMilliseconds')->willReturn($created);

        $client->send(
            'website',
            1234567,
            'unique.event.identifier',
            'payment.received',
            [
                'color' => 'red',
                'keyWithEmptyValue' => '',
                'number' => 0
            ]
        );

        $requestBody = $this->getMessageBody();

        $this->assertNotContains('keyWithEmptyValue', $requestBody);
    }

    /**
     * @test
     */
    public function itShouldRemoveEmptyStringValuesFromPayloadInChildArrays()
    {
        $created = (int)(microtime(true) * 1000);

        $client = $this->client(['timeInMilliseconds']);
        $client->method('timeInMilliseconds')->willReturn($created);

        $client->send(
            'website',
            1234567,
            'unique.event.identifier',
            'payment.received',
            [
                'color' => 'red',
                'keyWithEmptyChild' => [
                  'keyWithEmptyValue' => ''
                ],
                'keyWithEmptyChildInChild' => [
                  'keyWithEmptyChild' => [
                    'secondKeyWithEmptyValue' => ''
                  ],
                ],
                'keyWithEmptyArray' => [],
                'number' => 0
            ]
        );

        $requestBody = $this->getMessageBody();

        $this->assertContains('color', $requestBody);
        $this->assertContains('number', $requestBody);

        $this->assertNotContains('keyWithEmptyValue', $requestBody);
        $this->assertNotContains('secondKeyWithEmptyValue', $requestBody);
        $this->assertNotContains('keyWithEmptyArray', $requestBody);
        $this->assertNotContains('keyWithEmptyChild', $requestBody);
        $this->assertNotContains('keyWithEmptyChildInChild', $requestBody);
    }

    /**
     * @test
     */
    public function itShouldGenerateCorrectRequestBody()
    {
        $created = (int)(microtime(true) * 1000);

        $client = $this->client(['timeInMilliseconds']);
        $client->method('timeInMilliseconds')->willReturn($created);

        $client->send(
            'website',
            12345566,
            'unique.event.identifier',
            'payment.received',
            [
                'color' => 'red',
                'animal' => 'rabbit',
            ]
        );

        $requestBody = $this->getMessageBody();

        $expectedRequestBody = '{"id":"unique.event.identifier","name":' .
            '"payment.received","website":12345566,"created":' . $created .
            ',"payload":{"color":"red","animal":"rabbit"}}';

        $this->assertEquals($expectedRequestBody, $requestBody);
    }

    /**
     * @test
     */
    public function itShouldGenerateAlwaysIdOfTypeString()
    {
        $created =  (int)(microtime(true) * 1000);

        $client = $this->client(['timeInMilliSeconds']);
        $client->method('timeInMilliSeconds')->willReturn($created);

        $client->send(
            'website',
            12345566,
            123,
            'payment.received',
            [
                'color' => 'red',
                'animal' => 'rabbit',
            ]
        );

        $requestBody = $this->getMessageBody();

        $expectedRequestBody = '{"id":"123","name":' .
            '"payment.received","website":12345566,"created":' . $created .
            ',"payload":{"color":"red","animal":"rabbit"}}';

        $this->assertEquals($expectedRequestBody, $requestBody);
    }

    /**
     * @test
     */
    public function itShouldGenerateAlwaysJSONObjectInPayload()
    {
        $created =  (int)(microtime(true) * 1000);

        $client = $this->client(['timeInMilliSeconds']);
        $client->method('timeInMilliSeconds')->willReturn($created);

        $client->send(
            'website',
            12345566,
            123,
            'payment.received',
            []
        );

        $requestBody = $this->getMessageBody();

        $expectedRequestBody = '{"id":"123","name":' .
            '"payment.received","website":12345566,"created":' . $created .
            ',"payload":{}}';

        $this->assertEquals($expectedRequestBody, $requestBody);
    }

    /**
     * @test
     */
    public function itShouldGenerateRequestForCorrectQueueUrlMagically()
    {
        $created = (int)(microtime(true) * 1000);

        $client = $this->client(['timeInMilliseconds']);
        $client->method('timeInMilliseconds')->willReturn($created);

        $client->website(
            12345566,
            'unique.event.identifier',
            'payment.received',
            [
                'color' => 'red',
                'animal' => 'rabbit',
            ]
        );

        $requestBody = $this->getMessageBody();

        $expectedRequestBody = '{"id":"unique.event.identifier","name":' .
            '"payment.received","website":12345566,"created":' . $created .
            ',"payload":{"color":"red","animal":"rabbit"}}';

        $this->assertEquals($expectedRequestBody, $requestBody);
    }

    /**
     * @param array $methodsToStub
     * @return Client|PHPUnit_Framework_MockObject_MockObject
     */
    private function client($methodsToStub)
    {
        return $this->getMockBuilder('Jimdo\Notification\Event\Client')
            ->setConstructorArgs([$this->config(), $this->sqsClient()])
            ->setMethods($methodsToStub)
            ->getMock();
    }

    /**
     * @return Client\Config
     */
    private function config()
    {
        $queueUrl = 'https://sqs.eu-west-1.amazonaws.com/1234567890/ns-api-events';
        $awsKey = 'AWS_ACCESS_KEY';
        $awsSecret = 's0m3aw5s3cr31';
        $awsRegion = 'eu-west-1';

        return new Client\Config(
            $queueUrl,
            $awsKey,
            $awsSecret,
            $awsRegion
        );
    }

    /**
     * @return \GuzzleHttp\Client
     */
    private function handlers()
    {
        $stack = HandlerStack::create(new MockHandler([
            new Response(200, [], $this->cannedResponseForAllSqsRequests)
        ]));

        $history = Middleware::history($this->requests);
        $stack->push($history);

        return $stack;
    }

    /**
     * @return \Aws\Sqs\SqsClient
     */
    private function sqsClient()
    {
        return  new SqsClient([
            'credentials' => new Credentials($this->config()->key(), $this->config()->secret()),
            'region' => $this->config()->region(),
            'version' => Client::SQS_API_VERSION,
            'http_handler' => $this->handlers(),
        ]);
    }

    /**
     * @return string
     */
    private function getMessageBody()
    {
        $requestBody = (string) $this->requests[0]['request']->getBody();
        $queryParams = [];
        parse_str(urldecode($requestBody), $queryParams);
        return $queryParams['MessageBody'];
    }
}
