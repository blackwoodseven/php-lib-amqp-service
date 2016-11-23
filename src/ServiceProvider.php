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
        $app['amqp.ensure_topology'] = true;

        // Default queue and exchange shortcuts.
        $app['amqp.queue_name'] = function () use ($app) {
            return current(array_keys($app['amqp.queues']));
        };
        $app['amqp.exchange_name'] = function () use ($app) {
            return current(array_keys($app['amqp.exchanges']));
        };
    }

    public function boot(Application $app)
    {
        if ($app['amqp.ensure_topology']) {
            $this->ensureTopology(
                $app['amqp.channel'],
                $app['amqp.queues'],
                $app['amqp.exchanges']
            );
        }
    }

    protected function ensureTopology($channel, $queues, $exchanges)
    {
        $this->declareQueues($channel, $queues);
        $this->declareExchanges($channel, $exchanges);
        $this->bindQueues($channel, $queues);
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
        $definition += [
            'arguments' => [],
        ];

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

    protected function bindQueues($channel, $queues)
    {
        foreach ($queues as $queueName => $definition) {
            foreach ($definition['bindings'] as $exchangeName => $routingKeys) {
                foreach ((array) $routingKeys as $routingKey) {
                    $channel->queue_bind($queueName, $exchangeName, $routingKey);
                }
            }
        }
    }
}
