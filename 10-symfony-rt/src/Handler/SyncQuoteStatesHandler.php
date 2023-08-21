<?php

namespace App\Handler;

use App\Entity\Quote;
use App\Repository\QuoteRepository;
use App\Services\SyncQuoteState\FailureNotificationService;
use App\Services\SyncQuoteState\RetryDecisionService;
use App\Services\SyncQuoteState\SyncService;
use App\Traits\HasLogger;
use Psr\Log\LoggerAwareInterface;

class SyncQuoteStatesHandler implements LoggerAwareInterface
{
    use HasLogger;

    public function __construct(
        private QuoteRepository $quoteRepository,
        private RetryDecisionService $decisionService,
        private SyncService $syncService,
        private FailureNotificationService $failureNotifier,
    )
    {
    }

    /**
     * @return Quote[] Synced quotes
     */
    public function handle(): array
    {
        $quotes = $this->quoteRepository->findPendingForStateUpdate();

        $count = count($quotes);
        $this->logInfo("Found {$count} quotes for state sync.");

        $result = [];
        foreach ($quotes as $quote) {
            try {
                if ($this->handleQuote($quote)) {
                    $result[] = $quote;
                }
            } catch (\Exception $e) {
                $this->logCritical("{$quote}: failed to handle state sync. {$e}");
            }
        }

        return $result;
    }

    public function handleQuote(Quote $quote): bool
    {
        if ($this->decisionService->shouldSync($quote)) {
            $this->logInfo("{$quote}: syncing state.");
            $this->syncService->sync($quote);
            $result = true;
        } else {
            $this->logInfo("{$quote}: skipping a state sync.");
            $result = false;
        }

        if ($this->decisionService->shouldNotifyFailure($quote)) {
            $this->logInfo("{$quote}: notifying about failed sync tries.");
            $this->failureNotifier->notify($quote);
        }

        return $result;
    }
}
