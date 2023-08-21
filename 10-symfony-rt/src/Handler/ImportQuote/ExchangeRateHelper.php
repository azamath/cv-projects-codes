<?php
/**
 * Author: Azamat Holmeer
 */

namespace App\Handler\ImportQuote;

use App\Dto\Collection\ExchangeRateCollection;
use App\Entity\QuoteExchangeRate;

class ExchangeRateHelper
{
    public function alignRatesToBaseCurrency(ExchangeRateCollection $rates, string $baseCurrencyCode): void
    {
        if (!$rates->containsCode($baseCurrencyCode)) {
            return;
        }

        $baseRate = $rates->getByCode($baseCurrencyCode)->getRate();

        /** @var QuoteExchangeRate $rate */
        foreach ($rates as $rate) {
            $rate->setConversionRate($rate->getConversionRate() / $baseRate);
        }
    }
}
