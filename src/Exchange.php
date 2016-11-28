<?php
namespace BlackwoodSeven\AmqpService;

use PhpAmqpLib\Connection\AMQPLazyConnection;
use PhpAmqpLib\Message\AMQPMessage;

class Exchange implements \ArrayAccess
{
    private $name;
    private $definition;
    private $connection;
    private $channel;
    private $appId;

    public function __construct(AMQPLazyConnection $connection, $name, array $definition, $appId)
    {
        $this->connection = $connection;
        $this->name = $name;
        $this->definition = $definition + [
            'type' => 'topic',      // "topic", "fanout" etc.
            'passive' => false,     // passive; false => ignore if exchange already exists.
            'durable' => true,      // durable
            'auto_delete' => false, // auto_delete
        ];
        $this->appId = $appId;
    }

    public function getName()
    {
        return $this->name;
    }


    public function declare()
    {
        if (!isset($this->channel)) {
            $this->channel = $this->connection->channel();
            $this->channel->exchange_declare(
                $this->name,
                $this->definition['type'],
                $this->definition['passive'],
                $this->definition['durable'],
                $this->definition['auto_delete']
            );
        }
    }

    public function publish($routingKey, $type, $payload)
    {
        $this->declare();
        $json = json_encode($payload);
        $message = new AMQPMessage(
            $json,
            [
                'type' => $type,
                'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT,
                'app_id' => $this->appId,
                'content_type' => 'application/json',
            ]
        );
        $this->channel->basic_publish(
            $message,
            $this->name,
            $routingKey
        );
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
