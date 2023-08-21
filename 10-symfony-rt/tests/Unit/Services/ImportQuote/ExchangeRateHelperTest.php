<?php
/**
 * Author: Azamat Holmeer
 */

namespace App\Tests\Unit\Services\ImportQuote;

use App\Dto\Collection\ExchangeRateCollection;
use App\Entity\QuoteExchangeRate;
use App\Handler\ImportQuote\ExchangeRateHelper;
use PHPUnit\Framework\TestCase;

class ExchangeRateHelperTest extends TestCase
{

    public function testAlignRatesToBaseCurrency()
    {
        /** @var QuoteExchangeRate[]|ExchangeRateCollection $rates */
        $rates = new ExchangeRateCollection([
            (new QuoteExchangeRate())->setCurrencyCode('EUR')->setConversionRate(5),
            (new QuoteExchangeRate())->setCurrencyCode('USD')->setConversionRate(10),
            (new QuoteExchangeRate())->setCurrencyCode('SEK')->setConversionRate(1),
        ]);

        (new ExchangeRateHelper())->alignRatesToBaseCurrency($rates, 'EUR');

        $this->assertEquals(1, $rates[0]->getConversionRate());
        $this->assertEquals(2, $rates[1]->getConversionRate());
        $this->assertEquals(0.2, $rates[2]->getConversionRate());
    }
}
