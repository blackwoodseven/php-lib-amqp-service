<?php

namespace BlackwoodSeven\AmqpService\MessageHandler;

use PhpAmqpLib\Message\AMQPMessage;

/**
 * Dispatches a message for a routing key to a message handler.
 */
class RoutingKeyDispatcher extends MessageHandlerBase
{
    protected $handlers = [];

    /**
     * @see MessageHandlerInterface::handleMessage().
     */
    public function handleMessage(AMQPMessage $msg)
    {
        $handler = $this->determineHandler($msg->delivery_info['routing_key']);
        $handler->handleMessage($msg);
    }

    /**
     * Set handler for a routing key.
     *
     * @param string $routingKey
     *   Glob-style pattern of routing key(s) to match.
     * @param MessageHandlerInterface $handler
     *   The message handler to dispatch to.
     */
    public function setHandler($routingKey, MessageHandlerInterface $handler)
    {
        $routingKeyRegexPattern = $routingKey;
        $routingKeyRegexPattern = str_replace('.', '\.', $routingKeyRegexPattern);
        $routingKeyRegexPattern = str_replace('*', '.*', $routingKeyRegexPattern);
        $routingKeyRegexPattern = str_replace('#', '.*', $routingKeyRegexPattern);
        $routingKeyRegexPattern = "/^$routingKeyRegexPattern\$/";
        $this->handlers[$routingKeyRegexPattern] = $handler;
    }

    /**
     * Determine which handler to use for a specific routing key.
     *
     * @param string $routingKey
     *   The routing key in question.
     *
     * @return MessageHandlerInterface
     *   The message handler found for this routing key.
     */
    protected function determineHandler($routingKey): MessageHandlerInterface
    {
        foreach ($this->handlers as $routingKeyRegexPattern => $handler) {
            if (preg_match($routingKeyRegexPattern, $routingKey)) {
                return $handler;
            }
        }
        throw new \RuntimeException('Handler not found for routing key: ' . $routingKey);
    }
}
