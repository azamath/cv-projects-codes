<?php

namespace App\Traits;

use Psr\Log\LoggerInterface;

trait HasLogger
{
    protected ?LoggerInterface $logger = null;

    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }

    protected function logEmergency(string $message, $context = [])
    {
        $this->logger && $this->logger->emergency($message, $context);
    }

    protected function logAlert(string $message, $context = [])
    {
        $this->logger && $this->logger->alert($message, $context);
    }

    protected function logCritical(string $message, $context = [])
    {
        $this->logger && $this->logger->critical($message, $context);
    }

    protected function logError(string $message, $context = [])
    {
        $this->logger && $this->logger->error($message, $context);
    }

    protected function logWarning(string $message, $context = [])
    {
        $this->logger && $this->logger->warning($message, $context);
    }

    protected function logNotice(string $message, $context = [])
    {
        $this->logger && $this->logger->notice($message, $context);
    }

    protected function logInfo(string $message, $context = [])
    {
        $this->logger && $this->logger->info($message, $context);
    }

    protected function logDebug(string $message, $context = [])
    {
        $this->logger && $this->logger->debug($message, $context);
    }
}
