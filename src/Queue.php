<?php
namespace BlackwoodSeven\AmqpService;

use PhpAmqpLib\Connection\AMQPConnection;
use PhpAmqpLib\Channel\AMQPChannel;

class Queue implements \ArrayAccess
{
    private $name;
    private $definition;
    private $appId;
    private $channel;

    public function __construct($name, array $definition, $appId = '')
    {
        $this->name = $name;
        $this->definition = $definition + [
            'passive' => false,         // passive; false => ignore if exchange already exists.
            'durable' => true,          // durable
            'exclusive' => false,       // exclusive
            'auto_delete' => false,     // auto_delete
            'nowait' => false,          // nowait
            'arguments' => [],
            'bindings' => [],
        ];
        $this->appId = $appId;
    }

    public function getName()
    {
        return $this->name;
    }

    public function declare(AMQPChannel $channel)
    {
        $channel->queue_declare(
            $this->name,
            $this->definition['passive'],
            $this->definition['durable'],
            $this->definition['exclusive'],
            $this->definition['auto_delete'],
            $this->definition['nowait'],
            $this->definition['arguments']
        );
    }

    public function bind(AMQPChannel $channel, Exchange $exchange, $routingKeys)
    {
        $this->channel = $channel;
        $exchangeName = $exchange->getName();
        foreach ((array) $routingKeys as $routingKey) {
            $channel->queue_bind($this->name, $exchangeName, $routingKey);
        }
        return $this;
    }


    public function listen(callable $callback, $no_local = false, $no_ack = false, $exclusive = false, $nowait = false)
    {
        if (!$this->channel) {
            throw new \Exception('unbound');
        }
        $this->channel->basic_consume(
            $this->name,
            $this->appId,
            $no_local,
            $no_ack,
            $exclusive,
            $nowait,
            $callback
        );

        while (count($this->channel->callbacks)) {
            $this->channel->wait();
        }
    }

    public function listenOnce(callable $callback)
    {
        if (!$this->channel) {
            throw new \Exception('unbound');
        }
        while ($msg = $this->channel->basic_get($this->name)) {
            $msg->delivery_info['channel'] = $this->channel;
            $callback($msg);
        }
    }

    /**
     * from \ArrayAccess
     */
    public function offsetExists($offset)
    {
        return isset($this->definition[$offset]);
    }
    public function offsetGet($offset)
    {
        return $this->definition[$offset];
    }
    public function offsetSet($offset, $value)
    {
        $this->definition[$offset] = $value;
    }
    public function offsetUnset($offset)
    {
        unset($this->definition[$offset]);
    }
}
