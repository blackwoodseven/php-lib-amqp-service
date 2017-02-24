<?php
namespace BlackwoodSeven\AmqpService;

use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Message\AMQPMessage;

class Queue
{
    private $name;
    private $definition;
    private $channel;

    /**
     * Constructor.
     *
     * @param AMQPChannel $channel
     *   An AMQP channel.
     * @param string $name
     *   Name of the queue.
     * @param array $definition
     *   The definition of the queue.
     */
    public function __construct(AMQPChannel $channel, string $name, array $definition)
    {
        $this->channel = $channel;
        $this->name = $name;
        $this->definition = $definition + [
            'passive' => false,         // passive; false => ignore if queue already exists.
            'durable' => true,          // durable
            'exclusive' => false,       // exclusive
            'auto_delete' => false,     // auto_delete
            'nowait' => false,          // nowait
            'arguments' => [],
            'bindings' => [],
        ];

        $this->channel->queue_declare(
            $this->name,
            $this->definition['passive'],
            $this->definition['durable'],
            $this->definition['exclusive'],
            $this->definition['auto_delete'],
            $this->definition['nowait'],
            $this->definition['arguments']
        );
    }

    /**
     * Get name of queue.
     *
     * @return string
     *   The name of the queue.
     */
    public function getName(): string
    {
        return $this->name;
    }

    public function getBindings()
    {
        return $this->definition['bindings'];
    }

    /**
     * Bind to an exchange.
     *
     * @param  Exchange $exchange
     *   The exchange to bind to,
     * @param array  $routingKeys
     *   The routing keys to bind.
     */
    public function bind(Exchange $exchange, array $routingKeys)
    {
        foreach ($routingKeys as $routingKey) {
            $this->channel->queue_bind($this->name, $exchange->getName(), $routingKey);
        }
    }

    /**
     * Listen to a queue.
     *
     * @see AMQPChannel::basic_consume()
     */
    public function listen(callable $callback, $consumer_tag = '', $no_local = false, $no_ack = false, $exclusive = false, $nowait = false)
    {
        if (!$this->channel) {
            throw new \Exception('unbound');
        }

        $callbackWrapper = function (AMQPMessage $msg) use ($callback) {
            $this->dispatch($msg, $callback);
        };

        $this->channel->basic_consume(
            $this->name,
            $consumer_tag,
            $no_local,
            $no_ack,
            $exclusive,
            $nowait,
            $callbackWrapper
        );

        while (count($this->channel->callbacks)) {
            $this->channel->wait();
        }
    }

    /**
     * Listen to the queue once.
     *
     * @see AMQPChannel::basic_get().
     */
    public function listenOnce(callable $callback, $no_ack = false)
    {
        while ($msg = $this->channel->basic_get($this->name, $no_ack)) {
            $msg->delivery_info['channel'] = $this->channel;
            $this->dispatch($msg, $callback);
        }
    }

    private function dispatch(AMQPMessage $msg, callable $callback)
    {
        try {
            call_user_func($callback, $msg);
            $msg->delivery_info['channel']->basic_ack($msg->delivery_info['delivery_tag']);
        }
        catch (\Exception $e) {
            $msg->delivery_info['channel']->basic_nack($msg->delivery_info['delivery_tag']);
            throw $e;
        }
    }
}
