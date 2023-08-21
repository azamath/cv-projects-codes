<?php
/**
 * Author: Azamat Holmeer
 */

namespace App\Entity;

/**
 * Interface for any entity that represents an exchange rate
 */
interface ExchangeRateInterface
{
    public function getCurrencyCode(): string;

    public function getRate(): float;
}
