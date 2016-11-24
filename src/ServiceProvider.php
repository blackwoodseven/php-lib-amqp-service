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
            $channel = $app['amqp.connection']->channel();
            return $channel;
        };
        $app->extend('amqp.channel', function ($channel, Application $app) {
            $this->declareQueues($channel, $this->queues);
            $this->declareExchanges($channel, $app['amqp.exchanges']);
            return $channel;
        });

        // Default settings.
        $app['amqp.options'] = [];
        $app['amqp.queues'] = [];
        $app['amqp.exchanges'] = [];
    }

    public function boot(Application $app)
    {
        $this->queues = $app['amqp.queues'];
        foreach ($this->queues as &$definition) {
            $definition += [
                'arguments' => [],
            ];
        }
        $app['amqp.queues'] = function (Application $app) {
            $channel = $app['amqp.channel'];
            $queues = [];
            foreach ($this->queues as $name => $definition) {
                $queues[$name] = new Queue($name, $definition, $app['amqp.options']['product']);
                $queues[$name]->bind($channel);
            }
            return $queues;
        };

        $app['amqp.queue'] = function (Application $app) {
            $name = current(array_keys($this->queues));
            return $app['amqp.queues'][$name];
        };
    }

    protected function declareExchanges($channel, $exchanges)
    {
        foreach ($exchanges as $exchangeName => $definition) {
            $this->declareExchange($channel, $exchangeName, $definition);
        }
    }

    /**
     * @throws AMQPProtocolException if exchange has already been defined with different parameters
     */
    protected function declareExchange($channel, $exchangeName, $definition)
    {
        $channel->exchange_declare(
            $exchangeName,
            $definition['type'], // "topic", "fanout" etc.
            false, // passive; false => ignore if exchange already exists.
            true, // durable
            false // auto_delete
        );
    }

    protected function declareQueues($channel, $queues)
    {
        foreach ($queues as $queueName => $definition) {
            $this->declareQueue($channel, $queueName, $definition);
        }
    }

    /**
     * @throws AMQPProtocolException if queue has already been defined with different parameters
     */
    protected function declareQueue($channel, $queueName, $definition)
    {
        $channel->queue_declare(
            $queueName,
            false, // passive; false => ignore if exchange already exists.
            true, // durable
            false, // exclusive
            false, // auto_delete
            false, // nowait
            $definition['arguments']
        );
    }
}
