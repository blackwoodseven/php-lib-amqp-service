## Installation

To install the BlackwoodSeven file library in your project using Composer:

```composer require blackwoodseven/php-lib-amqp-service```

## Usage:

```php

$app->register(new \BlackwoodSeven\AmqpService\ServiceProvider(), [
    'amqp_service.options' => [
        'dsn' => 'amqp://user:pass@host:port/vhost',
    ],
    // don't declare and bind queues and exchanges upon boot.
    // defaults to true
    'amqp_service.ensure_topology' => false,
    'definitions' => [
        'exchanges' => [
            'my_exchange_1' => [
                'type' => 'topic',
            ],
        ],
        'queues' => [
            'my_queue_q' => [
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

```
