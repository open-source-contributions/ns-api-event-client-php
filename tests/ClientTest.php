<?php

namespace Jimdo\Notification\Event;

use PHPUnit\Framework\TestCase;

use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;


class ClientTest extends TestCase
{
    private $requests;

    protected function setUp()
    {
        $this->requests = [];
    }

    /**
     * @test
     */
    public function itShouldGenerateCorrectRequestBody()
    {
        $created = time();

        $client = $this->client(['timestamp']);
        $client->method('timestamp')->willReturn($created);

        $client->send(
            'website',
            12345566,
            'payment.received',
            [
                'color' => 'red',
                'animal' => 'rabbit',
            ]
        );

        $requestBody = $this->getRequestBody();

        $expectedRequestBody = '{"name":"payment.received","website":12345566,"created":' .
            $created . ',"payload":{"color":"red","animal":"rabbit"}}';

        $this->assertEquals($expectedRequestBody, $requestBody);
    }

    /**
     * @test
     */
    public function itShouldGenerateRequestForCorrectEndpointMagicallyy()
    {
        $created = time();

        $client = $this->client(['timestamp']);
        $client->method('timestamp')->willReturn($created);

        $client->website(
            12345566,
            'payment.received',
            [
                'color' => 'red',
                'animal' => 'rabbit',
            ]
        );

        $requestBody = $this->getRequestBody();

        $expectedRequestBody = '{"name":"payment.received","website":12345566,"created":' .
            $created . ',"payload":{"color":"red","animal":"rabbit"}}';

        $this->assertEquals($expectedRequestBody, $requestBody);
    }

    /**
     * @param array $methodsToStub
     * @return Client|PHPUnit_Framework_MockObject_MockObject
     */
    private function client($methodsToStub)
    {
        return $this->getMockBuilder('Jimdo\Notification\Event\Client')
            ->setConstructorArgs([$this->config(), $this->httpClient(), new DummySignature()])
            ->setMethods($methodsToStub)
            ->getMock();
    }

    /**
     * @return Client\Config
     */
    private function config()
    {
        $endpoint = 'https://my-lambda-endpoint.execute-api.eu-west-1.amazonaws.com';
        $staging = 'v1';
        $awsKey = 'AWS_ACCESS_KEY';
        $awsSecret = 's0m3aw5s3cr31';
        $awsRegion = 'eu-west-1';

        return new Client\Config(
            $endpoint,
            $staging,
            $awsKey,
            $awsSecret,
            $awsRegion
        );
    }

    /**
     * @return \GuzzleHttp\Client
     */
    private function httpClient()
    {
        $stack = HandlerStack::create(new MockHandler([
            new Response(200)
        ]));

        $history = Middleware::history($this->requests);
        $stack->push($history);

        return new HttpClient(['handler' => $stack]);
    }

    /**
     * @return string
     */
    private function getRequestBody()
    {
        return (string) $this->requests[0]['request']->getBody();
    }
}
