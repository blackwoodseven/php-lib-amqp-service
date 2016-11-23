## Installation

To install the BlackwoodSeven file library in your project using Composer:

```composer require blackwoodseven/php-lib-amqp-service```

## Usage:

```php

$app->register(new \BlackwoodSeven\AmqpService\ServiceProvider(), [
    'amqp.options' => [
        'product' => 'my_app_id',
        'dsn' => 'amqp://user:pass@host:port/vhost',
    ],
    // don't declare and bind queues and exchanges upon boot.
    // defaults to true
    'amqp.ensure_topology' => false,
    'amqp.exchanges' => [
        'my_exchange_1' => [
            'type' => 'topic',
        ],
    ],
    'amqp.queues' => [
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
]);

```
