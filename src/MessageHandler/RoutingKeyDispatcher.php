<?php

namespace BW7\MessageHandler;

use PhpAmqpLib\Message\AMQPMessage;

class RoutingKeyDispatcher implements MessageHandlerInterface
{
    protected $handlers = [];

    public function handleMessage(AMQPMessage $msg)
    {
        $handler = $this->determineHandler($msg->delivery_info['routing_key']);
        $handler->handleMessage($msg);
    }

    public function setHandler($routingKey, MessageHandlerInterface $handler)
    {
        $routingKeyRegexPattern = $routingKey;
        $routingKeyRegexPattern = str_replace('.', '\.', $routingKeyRegexPattern);
        $routingKeyRegexPattern = str_replace('*', '.*', $routingKeyRegexPattern);
        $routingKeyRegexPattern = str_replace('#', '.*', $routingKeyRegexPattern);
        $routingKeyRegexPattern = "/^$routingKeyRegexPattern\$/";
        $this->handlers[$routingKeyRegexPattern] = $handler;
    }

    protected function determineHandler($routingKey)
    {
        foreach ($this->handlers as $routingKeyRegexPattern => $handler) {
            if (preg_match($routingKeyRegexPattern, $routingKey)) {
                return $handler;
            }
        }
        throw new \RuntimeException('Handler not found for routing key: ' . $routingKey);
    }
}
