<?php

namespace App\Services\SyncQuoteState;

use App\Entity\Quote;
use App\Notification\StateSyncFailureNotification;
use App\Traits\HasLogger;
use DateTime;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerAwareInterface;
use Symfony\Component\Notifier\NotifierInterface;

class FailureNotificationService implements LoggerAwareInterface
{
    use HasLogger;

    public function __construct(private NotifierInterface $notifier, private ManagerRegistry $doctrine)
    {
    }

    public function notify(Quote $quote)
    {
        $notification = new StateSyncFailureNotification($quote);
        $recipients = method_exists($this->notifier, 'getAdminRecipients') ? $this->notifier->getAdminRecipients() : [];
        $this->notifier->send($notification, ...$recipients);
        $this->logNotice("{$quote}: failure notification has been sent.");

        $quote->setStateSyncFailureNotifiedDate(new DateTime());
        $this->doctrine->getManager()->flush();
    }
}
