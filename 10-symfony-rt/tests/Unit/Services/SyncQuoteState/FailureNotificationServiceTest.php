<?php

namespace App\Tests\Unit\Services\SyncQuoteState;

use App\Entity\Quote;
use App\Services\SyncQuoteState\FailureNotificationService;
use App\Tests\Traits\MocksDoctrine;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Notifier\NotifierInterface;

class FailureNotificationServiceTest extends TestCase
{
    use MocksDoctrine;

    public function testNotificationSent()
    {
        $quote = new Quote();
        $this->assertNull($quote->getStateSyncFailureNotifiedDate());

        $mockNotifier = $this->createMock(NotifierInterface::class);
        //$mockNotifier->method('getAdminRecipients')->willReturn([]);
        $mockNotifier->expects($this->once())->method('send');

        $service = new FailureNotificationService(
            $mockNotifier,
            $this->getMockDoctrine(),
        );

        $service->notify(
            $quote,
        );

        $this->assertNotNull($quote->getStateSyncFailureNotifiedDate());
    }
}
