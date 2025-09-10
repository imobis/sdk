<?php

namespace Imobis\Sdk;

use Imobis\Sdk\Core\Singleton;
use Monolog\Handler\StreamHandler;

class Logger extends Singleton
{
    private object $stream_handler;
    private object $logger;

    protected function __construct(string $stream = Config::LOGGER_STREAM)
    {
        parent::__construct();
        $this->logger = new \Monolog\Logger(Config::LOGGER_NAME);
        $this->stream_handler = new StreamHandler($stream);
        $this->logger->pushHandler($this->stream_handler);
    }

    public static function log(string $level, string $message, array $context = []): string
    {
        $logger = static::getInstance();

        return $logger->writeLog($level, $message, $context);
    }

    private function writeLog(string $level, string $message, array $context = []): string
    {
        if (method_exists($this->logger, "{$level}")) {
            if (!isset($context['log_hash'])) {
                $context['log_hash'] = md5($level . $message . json_encode($this->logger, JSON_UNESCAPED_UNICODE));
            }
            $this->logger->{$level}($message, $context);
        }

        return $context['log_hash'] ?? '';
    }
}