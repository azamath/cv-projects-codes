<?php
/**
 * Author: Azamat Holmeer
 */

namespace App\Dto\ImportQuote;

class Quote
{
    public $quoteNumber;
    public ?string $vendorName = null;
    public ?\DateTime $expirationDate = null;
    public ?\DateTime $supportExpirationDate = null;
    public $currencyCode = null;
    public EndCustomer $endCustomer;
    /** @var Product[] */
    public array $products;
    /** @var UpsellProduct[] */
    public array $upsellProducts = [];
    /** @var ExchangeRate[] */
    public array $exchangeRates = [];
    public ?Address $invoiceAddress = null;
    public ?Address $shippingAddress = null;
    public ?bool $orderable = null;
    public ?string $dealId = null;
    public ?string $solutionID = null;
}
