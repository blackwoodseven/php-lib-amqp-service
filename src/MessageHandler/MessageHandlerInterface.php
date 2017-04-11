<?php

namespace BlackwoodSeven\AmqpService\MessageHandler;

use PhpAmqpLib\Message\AMQPMessage;

interface MessageHandlerInterface
{
    public function handleMessage(AMQPMessage $msg);
}
