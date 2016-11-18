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
        $app['amqp_service.connection'] = function () use ($app) {
            $options = $app['amqp_service.options'];

            if (isset($app['console.name'])) {
                // Convert "bw7:messagequeue:foo" to "foo", because RabbitMQ Admin only shows first 10 characters.
                $parts = explode(':', $app['console.name']);
                $name = end($parts);
                AMQPConnection::$LIBRARY_PROPERTIES['product'] = ['S', $name];
            }

            $dsn = parse_url($options['dsn']);
            return new AMQPConnection(
                $dsn['host'],
                $dsn['port'],
                $dsn['user'],
                $dsn['pass'],
                rawurldecode(substr($dsn['path'], 1))
            );
        };

        $app['amqp_service.channel'] = function () use ($app) {
            return $app['amqp_service.connection']->channel();
        };

        // Default settings.
        $app['amqp_service.options'] = [];
        $app['amqp_service.definitions'] = [];
        $app['amqp_service.ensure_topology'] = true;
    }

    public function boot(Application $app)
    {
        if ($app['amqp_service.ensure_topology']) {
            $this->ensureTopology($app['amqp_service.channel'], $app['amqp_service.definitions'] + [
                'exchanges' => [],
                'queues' => [],
            ]);
        }
    }

    protected function ensureTopology($channel, $messageQueueDefinitions)
    {
        $this->declareQueues($channel, $messageQueueDefinitions['queues']);
        $this->declareExchanges($channel, $messageQueueDefinitions['exchanges']);
        $this->bindQueues($channel, $messageQueueDefinitions['queues']);
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
