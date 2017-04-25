<?php

namespace BlackwoodSeven\AmqpService\MessageHandler;

use PhpAmqpLib\Message\AMQPMessage;
use Psr\Log\LoggerInterface;

/**
 * Handle a message and log exception if caught. Always returns gracefully.
 */
class ExceptionLogger extends MessageHandlerBase
{
    /**
     * The message handler to dispatch to.
     *
     * @var MessageHandlerInterface
     */
    protected $messageHandler;

    /**
     * The logger to use for logging.
     *
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * Constructor.
     *
     * @param MessageHandlerInterface $messageHandler
     *   The message handler to wrap.
     * @param LoggerInterface $logger
     *   The logger to use for logging exceptions.
     */
    public function __construct(MessageHandlerInterface $messageHandler, LoggerInterface $logger)
    {
        $this->messageHandler = $messageHandler;
        $this->logger = $logger;
    }

    /**
     * Handle message, log exception if necessary, continue.
     *
     * @see MessageHandlerInterface::handleMessage()
     */
    public function handleMessage(AMQPMessage $msg)
    {
        try {
            $this->messageHandler->handleMessage($msg);
        } catch (\Exception $e) {
            $this->logger->error((string) $e);
        }
    }
}
