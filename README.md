# Notification Service Event API Client [![Build Status](https://travis-ci.com/Jimdo/ns-api-event-client-php.svg?token=xaHjcgAFSuULvgxb6q6z&branch=master)](https://travis-ci.com/Jimdo/ns-api-event-client-php)

The client's task is to put events in our *Notification Service SQS queue*. It accomplishes the following things:

  - assemble the event data and create the message body for the SQS request
  - use the SQS client to put the event data into the queue

## Usage

To obtain a client use the factory method:

```
use Jimdo\Notification\Event\Client;

$queueUrl = 'https://sqs.eu-west-1.amazonaws.com/1234567890/ns-api-events';
$awsAccessKey = 'MY_ACCESS_KEY';
$awsSecretKey = 'mys3cr3tk3y';
$awsRegion = 'eu-west-1';

$client = Client::create(
     $queueUrl,
     $awsAccessKey,
     $awsSecretKey,
     $awsRegion
);
```

### To send an event to the SQS queue you have two options

Via the `send()` method:

```
$client->send(
    'website',                // Target of the notification (website or group)
    123456789,                // Target identifier (website id or group descriptor)
    'unique.event.identifier' // A unique event identifier
    'payment.received',       // Namespace of the event
    [                         // Arbitrary payload in key value style
        'color' => 'red',
        'animal' => 'rabbit',
    ]
);
```

Via a *magic* method call:

```
# The method name represents the target of the notification (website or group)

$client->website(
    123456789,                // Website ID to send an event to
    'unique.event.identifier' // A unique event identifier
    'payment.received',       // Namespace of the event
    [                         // Arbitrary payload in key value style
        'color' => 'red',
        'animal' => 'rabbit',
    ]
);
```

**Note:** Although this client is capable of sending events to arbitrary targets the
*Notification Service* currently only supports the target `website`.

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
