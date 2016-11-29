<?php
namespace BlackwoodSeven\AmqpService;

use PhpAmqpLib\Connection\AMQPLazyConnection;

class Queue implements \ArrayAccess
{
    private $name;
    private $definition;
    private $connection;
    private $channel;

    /**
     * Constructor.
     *
     * @param AMQPLazyConnection $connection
     *   An AMQP lazy connection.
     * @param string $name
     *   Name of the queue.
     * @param array $definition
     *   The definition of the queue.
     */
    public function __construct(AMQPLazyConnection $connection, string $name, array $definition)
    {
        $this->connection = $connection;
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
    }

    /**
     * Get name of exchange.
     *
     * @return string
     *   The name of the exchange.
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Bind to an exchange.
     *
     * If queue has not been declared yet, bind will be postponed until it is
     * declared.
     *
     * @param  Exchange $exchange
     *   The exchange to bind to,
     * @param array  $routingKeys
     *   The routing keys to bind.
     */
    public function bind(Exchange $exchange, $routingKeys)
    {
        if (!$this->channel) {
            $this->bindings[$exchange->getName()] = [$exchange, $routingKeys];
        }
        else {
            $this->bindQueue($exchange, $routingKeys);
        }
    }

    /**
     * Listen to a queue.
     *
     * @see AMQPChannel::basic_consume()
     */
    public function listen(callable $callback, $consumer_tag = '', $no_local = false, $no_ack = false, $exclusive = false, $nowait = false)
    {
        $this->declare();
        if (!$this->channel) {
            throw new \Exception('unbound');
        }
        $this->channel->basic_consume(
            $this->name,
            $consumer_tag,
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

    /**
     * Listen to the queue once.
     *
     * @see AMQPChannel::basic_get().
     */
    public function listenOnce(callable $callback, $no_ack = false)
    {
        $this->declare();
        if (!$this->channel) {
            throw new \Exception('unbound');
        }
        while ($msg = $this->channel->basic_get($this->name, $no_ack)) {
            $msg->delivery_info['channel'] = $this->channel;
            $callback($msg);
        }
    }

    /**
     * Declare the exchange and bind to exchanges if not already declared.
     */
    public function declare()
    {
        if (!isset($this->channel)) {
            $this->channel = $this->connection->channel();
            $this->channel->queue_declare(
                $this->name,
                $this->definition['passive'],
                $this->definition['durable'],
                $this->definition['exclusive'],
                $this->definition['auto_delete'],
                $this->definition['nowait'],
                $this->definition['arguments']
            );
            foreach ($this->bindings as $info) {
                list ($exchange, $routingKeys) = $info;
                $this->bindQueue($exchange, $routingKeys);
            }
        }
    }

    /**
     * Actual bind to an exchange.
     *
     * @param  Exchange $exchange
     *   The exchange to bind to,
     * @param array  $routingKeys
     *   The routing keys to bind.
     */
    private function bindQueue(Exchange $exchange, array $routingKeys)
    {
        $exchange->declare();
        foreach ($routingKeys as $routingKey) {
            $this->channel->queue_bind($this->name, $exchange->getName(), $routingKey);
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
