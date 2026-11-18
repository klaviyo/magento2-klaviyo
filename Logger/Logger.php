<?php

namespace Klaviyo\Reclaim\Logger;

use Monolog\Logger as MonologLogger;
use Psr\Log\AbstractLogger;

class Logger extends AbstractLogger
{
    private $logger;

    public function __construct($name, array $handlers = [], array $processors = [], ?\DateTimeZone $timezone = null)
    {
        $this->logger = new MonologLogger($name, $handlers, $processors, $timezone);
    }

    /**
     * Write a message to the configured handlers.
     *
     * @param mixed $level
     * @param string|\Stringable $message
     * @param array $context
     * @return void
     */
    public function log($level, $message, array $context = []): void
    {
        $this->logger->log($level, $message, $context);
    }
}
