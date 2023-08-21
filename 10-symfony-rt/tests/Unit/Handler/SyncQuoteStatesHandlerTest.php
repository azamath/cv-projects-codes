<?php

namespace App\Tests\Unit\Handler;

use App\Entity\Quote;
use App\Handler\SyncQuoteStatesHandler;
use App\Repository\QuoteRepository;
use App\Services\SyncQuoteState\FailureNotificationService;
use App\Services\SyncQuoteState\RetryDecisionService;
use App\Services\SyncQuoteState\SyncService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class SyncQuoteStatesHandlerTest extends TestCase
{

    public function testHandleQuote(): void
    {
        $testQuotes = $this->getTestQuotes();

        $mockRetryDecisionService = $this->getMockRetryDecisionService();
        $mockRetryDecisionService->method('shouldSync')->willReturn(false, true, true);

        $mockSyncService = $this->getMockSyncService();
        $mockSyncService->expects($this->exactly(2))->method('sync');

        $handler = new SyncQuoteStatesHandler(
            $this->getMockQuoteService($testQuotes),
            $mockRetryDecisionService,
            $mockSyncService,
            $this->createMock(FailureNotificationService::class),
        );

        foreach ($testQuotes as $quote) {
            $handler->handleQuote($quote);
        }
    }

    public function testHandleQuoteNotifiesFailure(): void
    {
        $quote = new Quote();

        $mockDecisionService = $this->getMockRetryDecisionService();
        $mockDecisionService->method('shouldNotifyFailure')->willReturn(true);

        $mockFailureNotifier = $this->createMock(FailureNotificationService::class);
        $mockFailureNotifier->expects($this->once())
            ->method('notify')->with($this->equalTo($quote));

        $handler = new SyncQuoteStatesHandler(
            $this->getMockQuoteService(),
            $mockDecisionService,
            $this->getMockSyncService(),
            $mockFailureNotifier,
        );

        $handler->handleQuote($quote);
    }

    /**
     * @param array $testQuotes
     * @return QuoteRepository|MockObject
     */
    protected function getMockQuoteService(array $testQuotes = []): MockObject|QuoteRepository
    {
        $mock = $this->createMock(QuoteRepository::class);
        $mock->expects($this->any())
            ->method('findPendingForStateUpdate')
            ->willReturn($testQuotes);

        return $mock;
    }

    /**
     * @return Quote[]
     */
    protected function getTestQuotes(): array
    {
        return [
            (new Quote()),
            (new Quote()),
            (new Quote()),
        ];
    }

    /**
     * @return SyncService|mixed|MockObject
     */
    protected function getMockSyncService(): mixed
    {
        return $this->createMock(SyncService::class);
    }

    /**
     * @return RetryDecisionService|MockObject
     */
    protected function getMockRetryDecisionService(): RetryDecisionService|MockObject
    {
        return $this->createMock(RetryDecisionService::class);
    }
}
