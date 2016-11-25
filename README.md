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

$default_queue->listenOnce(function () {
    // do stuff...
});

$app['amqp.queue']['another_queue']->listenOnce(function () {
    // do stuff...
});



$default_exchange = $app['amqp.exchange'];

$default_exchange->publish('my.routing.key', 'my_type', ['message' => 'hello world']);

$app['amqp.exchange']['another_exchange']->publish('my.routing.key', 'my_type', ['message' => 'hello world']);

```
