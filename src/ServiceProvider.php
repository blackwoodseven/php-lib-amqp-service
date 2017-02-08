<?php
namespace BlackwoodSeven\AmqpService;

use PhpAmqpLib\Connection\AMQPLazyConnection;
use Pimple\ServiceProviderInterface;
use Pimple\Container;

class ServiceProvider implements ServiceProviderInterface
{
    public function register(Container $app)
    {
        $app['amqp.options.initializer'] = $app->protect(function () use ($app) {
            static $initialized = false;

            if ($initialized) {
                return;
            }

            $initialized = true;

            $app['amqp.options'] += [
                'queues' => [],
                'exchanges' => [],
            ];
        });

        $app['amqp.connection'] = function () use ($app) {
            $app['amqp.options.initializer']();

            if (empty($app['amqp.options']['product'])) {
                throw new \InvalidArgumentException('AmqpService: "product" must be specified.');
            }

            if (empty($app['amqp.options']['dsn'])) {
                throw new \InvalidArgumentException('AmqpService: "dsn" must be specified.');
            }

            AMQPLazyConnection::$LIBRARY_PROPERTIES['product'] = ['S', $app['amqp.options']['product']];

            $dsn = parse_url($app['amqp.options']['dsn']) + ['port' => 15672, 'user' => null, 'pass' => null, 'path' => '//'];
            return new AMQPLazyConnection(
                $dsn['host'],
                $dsn['port'],
                $dsn['user'],
                $dsn['pass'],
                rawurldecode(substr($dsn['path'], 1))
            );
        };

        $app['amqp.channel'] = function (Container $app) {
            return $app['amqp.connection']->channel();
        };

        // Provide exchanges through a DI container for singular lazy load.
        $app['amqp.exchanges'] = function (Container $app) {
            $app['amqp.options.initializer']();

            $exchanges = new Container();
            $firstExchangeName = null;
            foreach ($app['amqp.options']['exchanges'] as $name => $definition) {
                if (!is_array($definition)) {
                    $name = $definition;
                    $definition = [];
                }
                $firstExchangeName = $firstExchangeName ?? $name;
                $exchanges[$name] = function () use ($name, $definition, $app) {
                    return new Exchange($app['amqp.channel'], $name, $definition);
                };
            };
            $app['amqp.exchange.default'] = $firstExchangeName;
            return $exchanges;
        };

        // Provide the first exchange as the "default" exchange.
        $app['amqp.exchange'] = function (Container $app) {
            $exchanges = $app['amqp.exchanges'];
            if (!$app['amqp.exchange.default']) {
                throw new \RuntimeException('No exchanges defined.');
            }
            return $exchanges[$app['amqp.exchange.default']];
        };

        // Provide queues through a DI container for singular lazy load.
        $app['amqp.queues'] = function (Container $app) {
            $app['amqp.options.initializer']();

            $firstQueueName = null;
            $queues = new Container();
            foreach ($app['amqp.options']['queues'] as $name => $definition) {
                $firstQueueName = $firstQueueName ?? $name;
                $queues[$name] = function () use ($name, $definition, $app) {
                    $queue = new Queue($app['amqp.channel'], $name, $definition);
                    // Bind exchanges defined. This will also automatically
                    // declare the exchange.
                    foreach ($queue->getBindings() as $exchangeName => $routingKeys) {
                        $queue->bind($app['amqp.exchanges'][$exchangeName], $routingKeys);
                    }
                    return $queue;
                };
            }
            $app['amqp.queue.default'] = $firstQueueName;
            return $queues;
        };

        // Provide the first queue as the "default" queue.
        $app['amqp.queue'] = function (Container $app) {
            $queues = $app['amqp.queues'];
            if (!$app['amqp.queue.default']) {
                throw new \RuntimeException('No queues defined.');
            }
            return $queues[$app['amqp.queue.default']];
        };

        // Default settings.
        $app['amqp.options'] = [];
    }
}
