<?php


namespace App\Services\SyncQuoteState;

use App\Entity\Quote;

/**
 * Responsible for decisions to make sync tries and failure notifications
 */
class RetryDecisionService
{
    public const STOP_TRYING_ON = 4;

    /**
     * Decides whether to try to sync a quote state
     *
     * @param Quote $quote
     * @return bool
     */
    public function shouldSync(Quote $quote): bool
    {
        $triesCount = $quote->getStateSyncTries();
        $date = $quote->getStateSyncDate();

        if ($triesCount >= self::STOP_TRYING_ON) {
            return false;
        }

        // no sync happened earlier
        if (!$date) {
            return true;
        }

        $retryDelays = [
            0, // the first try
            30, // minutes
            3 * 60, // 3 hours
            24 * 60, // 24 hours
        ];

        $delay = $retryDelays[$triesCount];
        $minutesPassed = (time() - $date->getTimestamp()) / 60;

        return $minutesPassed >= $delay;
    }

    /**
     * Decides whether to send a failure notification
     *
     * @param Quote $quote
     * @return bool
     */
    public function shouldNotifyFailure(Quote $quote): bool
    {
        return $quote->getStateSyncTries() >= self::STOP_TRYING_ON && null == $quote->getStateSyncFailureNotifiedDate();
    }
}
