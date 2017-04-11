<?php

namespace BlackwoodSeven\AmqpService\MessageHandler;

use PhpAmqpLib\Message\AMQPMessage;

class ExceptionLogger extends MessageHandlerBase
{
    protected $messageHandler;
    protected $logger;

    public function __construct($messageHandler, $logger)
    {
        $this->messageHandler = $messageHandler;
        $this->logger = $logger;
    }

    public function handleMessage(AMQPMessage $msg)
    {
        try {
            $this->messageHandler->handleMessage($msg);
        }
        catch (\Exception $e) {
            $this->logger->error((string) $e);
        }
    }
}
