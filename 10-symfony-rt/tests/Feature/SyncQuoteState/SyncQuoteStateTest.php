<?php
/**
 * Author: Azamat Holmeer
 */

namespace App\Tests\Feature\SyncQuoteState;

use App\Entity\Quote;
use App\Enum\ESigningState;
use App\Factory\QuoteFactory;
use App\Factory\SigningFactory;
use App\Handler\ResolveQuoteStatesHandler;
use App\Handler\SyncQuoteStatesHandler;
use App\Services\SyncQuoteState\SyncService;
use App\Tests\Traits\ContainerHelpers;
use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Test\Factories;

class SyncQuoteStateTest extends KernelTestCase
{
    use ContainerHelpers;
    use Factories;

    protected function setUp(): void
    {
        parent::setUp();
        self::getContainer()->set(SyncService::class, $this->getMockSyncService());
    }

    public function testNothingSyncedWithQuotes()
    {
        QuoteFactory::new()
            ->withSameCompanies()
            ->withSameUser()
            ->withBaseSinging()
            ->sequence([
                ['resolvedState' => null, 'stateSynced' => false],
                ['resolvedState' => ESigningState::SOLD, 'stateSynced' => true],
            ])
            ->create();

        $this->getMockSyncService()->expects($this->never())->method('sync');

        $this->getResolveHandler()->handle();
        $this->getSyncHandler()->handle();
    }

    public function testSynced()
    {
        $quoteFactory = QuoteFactory::new()
            ->withSameCompanies()
            ->withSameUser();

        // quotes not to sync
        $quoteFactory
            ->many(2)
            ->create();

        $quotesToSync = $quoteFactory
            ->withBaseSinging()
            ->afterPersist(function (Quote $quote) {
                SigningFactory::new()
                    ->forQuote($quote)
                    ->signingState(ESigningState::SOLD)
                    ->create();
            })
            ->many(2)
            ->create();

        $quoteIdsToSync = (new ArrayCollection($quotesToSync))->map(fn($quote) => $quote->getQuoteId());

        $this->getMockSyncService()
            ->expects(self::exactly(count($quotesToSync)))
            ->method('sync');

        $this->getResolveHandler()->handle();
        $result = $this->getSyncHandler()->handle();

        foreach ($result as $syncedQuote) {
            $this->assertContains($syncedQuote->getQuoteId(), $quoteIdsToSync, 'wrong quote was synced');
        }
    }

    protected function getResolveHandler(): ResolveQuoteStatesHandler
    {
        return self::getContainer()->get(ResolveQuoteStatesHandler::class);
    }

    protected function getSyncHandler(): SyncQuoteStatesHandler
    {
        return self::getContainer()->get(SyncQuoteStatesHandler::class);
    }

    protected function getMockSyncService(): SyncService|MockObject
    {
        if (!isset($this->mockSyncService)) {
            $this->mockSyncService = $this->createMock(SyncService::class);
        }

        return $this->mockSyncService;
    }
}
