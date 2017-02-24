## Installation

To install the BlackwoodSeven file library in your project using Composer:

```composer require blackwoodseven/php-lib-amqp-service```

## Usage:

```php

$app->register(new \BlackwoodSeven\AmqpService\ServiceProvider(), [
    'amqp.options' => [
        'product' => 'my_app_id',
        'dsn' => 'amqp://user:pass@host:port/vhost',
        'exchanges' => [
            'my_exchange_0',
            'my_exchange_1' => [
                'type' => 'topic',
            ],
        ],
        'queues' => [
            'my_queue_1' => [
                'arguments' => [],
                'bindings' => [
                    'my_exchange_1' => [
                        'my_routingkey_1',
                        'my_routingkey_2',
                    ],
                ],
            ],
        ],
    ],
]);

$default_queue = $app['amqp.queue'];

$default_queue->listenOnce(function (\PhpAmqpLib\Message\AMQPMessage $msg) {
    // do stuff...
});

$app['amqp.queues']['another_queue']->listenOnce(function (\PhpAmqpLib\Message\AMQPMessage $msg) {
    // do stuff...
});



$default_exchange = $app['amqp.exchange'];

$default_exchange->publish(
    new \PhpAmqpLib\Message\AMQPMessage('Hello world'),
    'my.routing.key'
);

$app['amqp.exchanges']['another_exchange']->publish(
    new \PhpAmqpLib\Message\AMQPMessage('Hello world'),
    'my.routing.key'
);

```
