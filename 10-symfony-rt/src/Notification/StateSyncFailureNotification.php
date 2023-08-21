<?php

namespace App\Notification;

use App\Entity\Quote;
use Symfony\Component\Notifier\Notification\Notification;

class StateSyncFailureNotification extends Notification
{
    public function __construct(private Quote $quote)
    {
        parent::__construct();
    }

    public function getSubject(): string
    {
        return "[{$this->quote}] Failed to synchronize quote state";
    }

    public function getContent(): string
    {
        return <<<CONTENT
Quote ID: {$this->quote->getQuoteId()}
Quote Number: {$this->quote->getQuoteNumber()}
Remote Offer ID: {$this->quote->getBaseSigningId()} (Offer Tracking ID in an upstream node)
Quote State: {$this->quote->getResolvedState()->name}
Last tried at: {$this->quote->getStateSyncTryDate()->format('Y-m-d H:i:s T')}
CONTENT;
    }

    public function getImportance(): string
    {
        return Notification::IMPORTANCE_HIGH;
    }

}
