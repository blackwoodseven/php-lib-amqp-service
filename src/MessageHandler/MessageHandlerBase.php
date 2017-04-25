<?php

namespace BlackwoodSeven\AmqpService\MessageHandler;

use PhpAmqpLib\Message\AMQPMessage;

/**
 * Base class for message handlers.
 *
 * Provides an __invoke() method, so that the message handler may be used
 * directly with Queue::listen() and Queue::listenOnce().
 */
abstract class MessageHandlerBase implements MessageHandlerInterface
{
    abstract public function handleMessage(AMQPMessage $msg);

    public function __invoke(AMQPMessage $msg)
    {
        return $this->handleMessage($msg);
    }
}
