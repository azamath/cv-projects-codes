<?php

namespace App\Tests\Unit\Services\SyncQuoteState;

use App\Entity\Quote;
use App\Services\SyncQuoteState\RetryDecisionService;
use PHPUnit\Framework\TestCase;

class RetryDecisionServiceTest extends TestCase
{

    /**
     * @dataProvider shouldSyncDataProvider
     */
    public function testShouldSync(Quote $quote, $expect)
    {
        $actual = (new RetryDecisionService())->shouldSync($quote);
        $this->assertEquals($expect, $actual);
    }

    public function shouldSyncDataProvider(): array
    {
        return [
            [
                // allow first try
                (new Quote()),
                true,
            ],
            [
                // allow when sync date is unknown
                (new Quote())->setStateSyncDate(null)->setStateSyncTries(3),
                true,
            ],
            [
                // allow 2nd try
                (new Quote())->setStateSyncDate(new \DateTime('-30 minutes'))->setStateSyncTries(1),
                true,
            ],
            [
                // deny 2nd try
                (new Quote())->setStateSyncDate(new \DateTime('-29 minutes'))->setStateSyncTries(1),
                false,
            ],
            [
                // allow 3rd try
                (new Quote())->setStateSyncDate(new \DateTime('-3 hours'))->setStateSyncTries(2),
                true,
            ],
            [
                // deny 3rd try
                (new Quote())->setStateSyncDate(new \DateTime('-2 hours 59 minutes'))->setStateSyncTries(2),
                false,
            ],
            [
                // allow 4th try
                (new Quote())->setStateSyncDate(new \DateTime('-24 hours'))->setStateSyncTries(3),
                true,
            ],
            [
                // deny 4th try
                (new Quote())->setStateSyncDate(new \DateTime('-23 hours 59 minutes'))->setStateSyncTries(3),
                false,
            ],
            [
                // deny 5th try
                (new Quote())->setStateSyncDate(new \DateTime('-24 hours'))->setStateSyncTries(4),
                false,
            ],
        ];
    }

    /**
     * @dataProvider shouldNotifyFailureDataProvider
     */
    public function testShouldNotifyFailure(Quote $quote, $expect)
    {
        $actual = (new RetryDecisionService())->shouldNotifyFailure($quote);
        $this->assertEquals($expect, $actual);
    }

    public function shouldNotifyFailureDataProvider(): array
    {
        return [
            [
                (new Quote()),
                false,
            ],
            [
                (new Quote())->setStateSyncTries(RetryDecisionService::STOP_TRYING_ON - 2),
                false,
            ],
            [
                (new Quote())->setStateSyncTries(RetryDecisionService::STOP_TRYING_ON - 1),
                false,
            ],
            [
                (new Quote())
                    ->setStateSyncTries(RetryDecisionService::STOP_TRYING_ON)
                    ->setStateSyncFailureNotifiedDate(new \DateTime()),
                false,
            ],
            [
                (new Quote())
                    ->setStateSyncTries(RetryDecisionService::STOP_TRYING_ON)
                    ->setStateSyncFailureNotifiedDate(null),
                true,
            ],
        ];
    }
}
