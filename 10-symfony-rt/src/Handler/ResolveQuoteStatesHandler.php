<?php

namespace App\Handler;

use App\Entity\Quote;
use App\Enum\ESigningState;
use App\Repository\QuoteRepository;
use App\Traits\HasLogger;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerAwareInterface;

class ResolveQuoteStatesHandler implements LoggerAwareInterface
{
    use HasLogger;

    public function __construct(
        private QuoteRepository $quoteRepository,
        private ManagerRegistry $doctrine,
    )
    {
    }

    /**
     * @return Quote[]
     */
    public function handle(): array
    {
        $quotes = $this->quoteRepository->findPendingForStateResolve();

        $count = count($quotes);
        $this->logInfo("Found {$count} quotes for state resolve.");

        $resolved = [];
        foreach ($quotes as $quote) {
            try {
                if ($this->handleQuote($quote)) {
                    $resolved[] = $quote;
                }
            } catch (\Exception $e) {
                $this->logCritical("{$quote}: failed to handle state resolve. {$e}");
            }
        }

        return $resolved;
    }

    public function handleQuote(Quote $quote): bool
    {
        $signingStates = $this->quoteRepository->fetchSigningStatesForQuote($quote);
        $newState = $this->resolveState($signingStates);

        if ($quote->getResolvedState() === $newState) {
            $this->logInfo("{$quote}: no updates for state");
            return false;
        }

        $currentStateName = $quote->getResolvedState()->name ?? 'NULL';
        $this->logInfo("{$quote}: current state {$currentStateName}, new state {$newState->name}");
        $quote->setResolvedState($newState);
        $quote->setStateSynced(false);
        $quote->setStateSyncTries(0);
        $quote->setStateSyncTryDate(null);
        $quote->setStateSyncFailureNotifiedDate(null);
        $this->doctrine->getManager()->flush();
        $this->logNotice("{$quote}: queued for state sync");

        return true;
    }

    /**
     * @param ESigningState[] $states
     * @return ESigningState
     */
    public function resolveState(array $states): ESigningState
    {
        $priority = [
            ESigningState::SOLD,
            ESigningState::LOST,
            ESigningState::INACTIVATED,
        ];

        foreach ($priority as $state) {
            if (in_array($state, $states)) {
                return $state;
            }
        }

        return ESigningState::PENDING;
    }
}
