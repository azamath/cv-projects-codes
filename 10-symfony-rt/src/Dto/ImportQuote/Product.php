<?php
/**
 * Author: Azamat Holmeer
 */

namespace App\Dto\ImportQuote;

class Product
{
    public $sku;
    public $name;
    public $description = null;
    public $price = null;
    public $quantity = null;
    public $totalPrice = null;
    public $licenseNumber = null;
    public $startDate = null;
    public $endDate = null;
    public $annualList = null;
    public $extendedPrice = null;
    public $reinstatementFee = null;
    public $discount = null;
    public $rawData;
}
