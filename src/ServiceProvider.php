<?php
namespace BlackwoodSeven\AmqpService;

use PhpAmqpLib\Connection\AMQPConnection;
use Pimple\ServiceProviderInterface;
use Silex\Api\BootableProviderInterface;
use Pimple\Container;
use Silex\Application;

class ServiceProvider implements ServiceProviderInterface, BootableProviderInterface
{
    public function register(Container $app)
    {
        $app['amqp.connection'] = function () use ($app) {
            $options = $app['amqp.options'];

            if (empty($options['product'])) {
                throw new \InvalidArgumentException('AmqpService: "product" must be specified.');
            }

            if (empty($options['dsn'])) {
                throw new \InvalidArgumentException('AmqpService: "dsn" must be specified.');
            }

            AMQPConnection::$LIBRARY_PROPERTIES['product'] = ['S', $options['product']];

            $dsn = parse_url($options['dsn']);
            return new AMQPConnection(
                $dsn['host'],
                $dsn['port'],
                $dsn['user'],
                $dsn['pass'],
                rawurldecode(substr($dsn['path'], 1))
            );
        };

        $app['amqp.channel'] = function () use ($app) {
            return $app['amqp.connection']->channel();
        };

        // Default settings.
        $app['amqp.options'] = [];
        $app['amqp.queues'] = [];
        $app['amqp.exchanges'] = [];
    }

    public function boot(Application $app)
    {
        // Provide exchanges through a DI container for singular lazy load.
        $exchanges = new \Pimple\Container();
        $firstExchangeName = null;
        foreach ($app['amqp.exchanges'] as $name => $definition) {
            $firstExchangeName = $firstExchangeName ?? $name;
            $exchanges[$name] = function () use ($name, $definition, $app) {
                $channel = $app['amqp.channel'];
                $exchange = new Exchange($name, $definition, $app['amqp.options']['product']);
                $exchange->declare($channel);
                return $exchange;
            };
        };
        $app['amqp.exchanges'] = $exchanges;
        // Provide the first exchange as the "default" exchange.
        $app['amqp.exchange'] = function (Application $app) use ($firstExchangeName) {
            return $app['amqp.exchanges'][$firstExchangeName];
        };

        // Provide queues through a DI container for singular lazy load.
        $queues = new \Pimple\Container();
        $firstQueueName = null;
        foreach ($app['amqp.queues'] as $name => $definition) {
            $firstQueueName = $firstQueueName ?? $name;
            $queues[$name] = function () use ($name, $definition, $app) {
                $channel = $app['amqp.channel'];
                $queue = new Queue($name, $definition, $app['amqp.options']['product']);
                $queue->declare($channel);
                // Bind exchanges defined. Access to exchange will automatically
                // declare the exchange.
                foreach ($queue['bindings'] as $exchangeName => $routingKeys) {
                    $queue->bind($channel, $app['amqp.exchanges'][$exchangeName], $routingKeys);
                }
                return $queue;
            };
        };
        $app['amqp.queues'] = $queues;
        // Provide the first queue as the "default" queue.
        $app['amqp.queue'] = function (Application $app) use ($firstQueueName) {
            return $app['amqp.queues'][$firstQueueName];
        };
    }
}
