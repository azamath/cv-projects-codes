<?php
/**
 * Author: Azamat Holmeer
 */

namespace App\Tests\Feature\SyncQuoteState;

use App\Entity\Quote;
use App\Entity\Signing;
use App\Enum\ESigningState;
use App\Factory\QuoteFactory;
use App\Factory\SigningFactory;
use App\Handler\ResolveQuoteStatesHandler;
use App\Tests\Traits\ContainerHelpers;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Proxy;
use Zenstruck\Foundry\Test\Factories;

class ResolveQuoteStateTest extends KernelTestCase
{
    use ContainerHelpers;
    use Factories;

    public function testNothingToResolve()
    {
        $quoteFactory = QuoteFactory::new()
            ->withSameCompanies()
            ->withSameUser();

        $quoteFactory->create();
        $quoteFactory->withBaseSinging()->create();
        $quoteFactory->withBaseSinging()
            ->afterPersist(function (Quote $quote) {
                SigningFactory::new()
                    ->forQuote($quote)
                    ->signingState(ESigningState::SOLD)
                    ->afterInstantiate(function (Signing $signing) {
                        $signing->getSigningState()->setModifiedDate(date_create('-1 day'));
                    })
                    ->create();
            })
            ->create(['resolvedState' => ESigningState::SOLD, 'stateSyncDate' => date_create()]);

        $resolved = $this->getHandler()->handle();
        $this->assertEmpty($resolved);
    }

    public function testResolved()
    {
        $quotes = QuoteFactory::new()
            ->withSameCompanies()
            ->withSameUser()
            ->withBaseSinging()
            ->afterPersist(function (Quote $quote) {
                SigningFactory::new()
                    ->forQuote($quote)
                    ->afterInstantiate(function (Signing $signing) {
                        $signing->getSigningState()->setState(ESigningState::SOLD);
                        $signing->getSigningState()->setModifiedDate(date_create('-1 hour'));
                    })
                    ->create();
            })
            ->sequence([
                ['resolvedState' => null, 'stateSyncDate' => null],
                ['resolvedState' => ESigningState::PENDING, 'stateSyncDate' => date_create('-1 day')],
            ])
            ->create();

        $this->getHandler()->handle();

        foreach ($quotes as $quote) {
            /** @var Quote|Proxy $quote */
            $quote->refresh();
            $this->assertEquals(ESigningState::SOLD, $quote->getResolvedState());
            $this->assertFalse($quote->getStateSynced());
            $this->assertEquals(0, $quote->getStateSyncTries());
        }
    }

    protected function getHandler(): ResolveQuoteStatesHandler
    {
        return self::getContainer()->get(ResolveQuoteStatesHandler::class);
    }
}
