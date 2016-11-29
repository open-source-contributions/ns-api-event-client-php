# Notification Service Event API Client [![Build Status](https://travis-ci.com/Jimdo/ns-api-event-client-php.svg?token=xaHjcgAFSuULvgxb6q6z&branch=master)](https://travis-ci.com/Jimdo/ns-api-event-client-php)

The client's task is to talk to our *Notification Service Event API*. Therefore it encapsulates the following steps:

  - construct a request for the AWS API Gateway
  - sign the request with an *AWS SignatureV4*
  - send the request to the AWS API Gateway

## Usage

To obtain a client use the factory method:

```
use Jimdo\Notification\Event\Client;

$endpoint = 'https://my-lambda-endpoint.execute-api.eu-west-1.amazonaws.com';
$stage = 'v1';
$awsAccessKey = 'MY_ACCESS_KEY';
$awsSecretKey = 'mys3cr3tk3y';
$awsRegion = 'eu-west-1';

$client = Client::create(
     $endpoint,
     $stage,
     $awsAccessKey,
     $awsSecretKey,
     $awsRegion
);

```

### To send an event to the API Gateway you have to options

Via the `send()` method:

```
$client->send(
    'website',                // Lambda function's path fragment of the endpoint 
    123456789,                // Website ID to send an event to
    'payment.received',       // Namespace of the event
    [                         // Arbitrary payload in key value style
        'color' => 'red',
        'animal' => 'rabbit',
    ]
);
```

Via a *magic* method call:

```
# The method name represents the Lambda
# function's path fragment of the endpoint 

$client->website(
    123456789,                // Website ID to send an event to
    'payment.received',       // Namespace of the event
    [                         // Arbitrary payload in key value style
        'color' => 'red',
        'animal' => 'rabbit',
    ]
);
```

## Development

```
# Clone the repo
$ git clone git@github.com:Jimdo/ns-api-event-client-php.git

# Install composer and project dependencies
$ make bootstrap
```

## General Information

This repository provides a `Makefile` to help you speeding up your development process.

```
$ make help
bootstrap    Install composer
tests        Execute test suite and create code coverage report
update       Update composer packages
```
