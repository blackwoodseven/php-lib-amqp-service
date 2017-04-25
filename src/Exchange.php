<?php
namespace BlackwoodSeven\AmqpService;

use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Message\AMQPMessage;

class Exchange
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
     *   Name of the exchange.
     * @param array $definition
     *   The definition of the exchange.
     */
    public function __construct(AMQPChannel $channel, string $name, array $definition)
    {
        $this->channel = $channel;
        $this->name = $name;
        $this->definition = $definition + [
            'type' => 'topic',      // "topic", "fanout" etc.
            'passive' => false,     // passive; false => ignore if exchange already exists.
            'durable' => true,      // durable
            'auto_delete' => false, // auto_delete
        ];

        $this->channel->exchange_declare(
            $this->name,
            $this->definition['type'],
            $this->definition['passive'],
            $this->definition['durable'],
            $this->definition['auto_delete']
        );
    }

    /**
     * Get name of exchange.
     *
     * @return string
     *   The name of the exchange.
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Publish a message to the exchange.
     *
     * @see AMQPChannel::basic_publish()
     */
    public function publish(
        AMQPMessage $msg,
        $routing_key = '',
        $mandatory = false,
        $immediate = false,
        $ticket = null
    ) {
        return $this->channel->basic_publish($msg, $this->name, $routing_key, $mandatory, $immediate, $ticket);
    }

    /**
     * Publish a message to the exchange.
     *
     * @see AMQPChannel::basic_publish()
     */
    public function publishJson(
        $data,
        $routing_key = '',
        array $options = [],
        $mandatory = false,
        $immediate = false,
        $ticket = null
    ) {
        $options += [
            'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT,
            'content_type' => 'application/json',
        ];
        $msg = new AMQPMessage(json_encode($data), $options);
        return $this->channel->basic_publish($msg, $this->name, $routing_key, $mandatory, $immediate, $ticket);
    }
}
