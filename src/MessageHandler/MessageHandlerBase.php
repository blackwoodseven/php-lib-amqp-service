<?php

namespace BlackwoodSeven\AmqpService\MessageHandler;

use PhpAmqpLib\Message\AMQPMessage;

abstract class MessageHandlerBase implements MessageHandlerInterface
{
    abstract public function handleMessage(AMQPMessage $msg);

    public function __invoke(AMQPMessage $msg)
    {
        return $this->handleMessage($msg);
    }
}
