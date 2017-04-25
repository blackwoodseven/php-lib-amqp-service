<?php

namespace BlackwoodSeven\AmqpService\MessageHandler;

use PhpAmqpLib\Message\AMQPMessage;

interface MessageHandlerInterface
{
    /**
     * Handle a message.
     *
     * @param AMQPMessage $msg
     *   The message to handle.
     */
    public function handleMessage(AMQPMessage $msg);
}
