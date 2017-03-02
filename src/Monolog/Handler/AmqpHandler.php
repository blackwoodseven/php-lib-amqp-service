<?php

namespace BlackwoodSeven\AmqpService\Monolog\Handler;

use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Logger;
use Monolog\Formatter\LineFormatter;
use PhpAmqpLib\Message\AMQPMessage;
use BlackwoodSeven\AmqpService\Exchange;

class AmqpHandler extends AbstractProcessingHandler
{
    protected $exchange;
    protected $appId;
    protected $routingKey;
    protected $type;

    public function __construct(Exchange $exchange, $appId = null, $routingKey = 'generic.log', $type = 'log', $level = Logger::DEBUG, $bubble = true)
    {
        $this->exchange = $exchange;
        $this->appId = $appId ?? gethostname();
        $this->routingKey = $routingKey;
        $this->type = $type;
        parent::__construct($level, $bubble);
    }

    /**
     * {@inheritDoc}
     */
    protected function write(array $record)
    {
        $this->exchange->publishJson(
            [
                'subject' => $record['level_name'],
                'message' => $record['formatted'],
                'context' => $record['context'],
                'loglevel' => strtolower($record['level_name']),
            ],
            $this->routingKey,
            [
                'type' => $this->type,
                'app_id' => $this->appId,
            ]
        );
    }

    /**
     * Gets the default formatter.
     *
     * @return FormatterInterface
     */
    protected function getDefaultFormatter()
    {
        return new LineFormatter("%message% %extra%", null, true, true);
    }

}
