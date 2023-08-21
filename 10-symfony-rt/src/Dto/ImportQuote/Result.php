<?php
/**
 * Author: Azamat Holmeer
 */

namespace App\Dto\ImportQuote;

use App\Dto\Collection\ExchangeRateCollection;
use App\Entity\Company;
use App\Entity\Quote;
use App\Entity\QuoteCompany;
use App\Entity\QuoteExchangeRate;
use App\Entity\QuoteProduct;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

class Result
{
    /**
     * @var Company
     */
    private Company $endCustomer;

    /**
     * @var Quote
     */
    private Quote $quote;

    /**
     * @var QuoteCompany
     */
    private QuoteCompany $quoteCompany;

    /**
     * @var QuoteProduct[]|ArrayCollection
     */
    private ArrayCollection $products;

    /**
     * @var QuoteExchangeRate[]|ExchangeRateCollection
     */
    private ExchangeRateCollection $exchangeRates;

    private array $validationErrors = [];

    public function __construct()
    {
        $this->products = new ArrayCollection();
        $this->exchangeRates = new ExchangeRateCollection();
    }

    /**
     * @return Company
     */
    public function getEndCustomer(): Company
    {
        return $this->endCustomer;
    }

    /**
     * @param Company $endCustomer
     * @return Result
     */
    public function setEndCustomer(Company $endCustomer): Result
    {
        $this->endCustomer = $endCustomer;
        return $this;
    }

    /**
     * @return Quote
     */
    public function getQuote(): Quote
    {
        return $this->quote;
    }

    /**
     * @param Quote $quote
     * @return Result
     */
    public function setQuote(Quote $quote): Result
    {
        $this->quote = $quote;
        return $this;
    }

    /**
     * @return QuoteCompany
     */
    public function getQuoteCompany(): QuoteCompany
    {
        return $this->quoteCompany;
    }

    /**
     * @param QuoteCompany $quoteCompany
     * @return Result
     */
    public function setQuoteCompany(QuoteCompany $quoteCompany): Result
    {
        $this->quoteCompany = $quoteCompany;
        return $this;
    }

    /**
     * @return QuoteProduct[]|Collection
     */
    public function getProducts(): Collection
    {
        return $this->products;
    }

    /**
     * @param QuoteProduct[]|ArrayCollection $products
     */
    public function setProducts(ArrayCollection $products): Result
    {
        $this->products = $products;
        return $this;
    }

    /**
     * @return QuoteExchangeRate[]|ExchangeRateCollection
     */
    public function getExchangeRates(): ExchangeRateCollection
    {
        return $this->exchangeRates;
    }

    /**
     * @param QuoteExchangeRate[]|ExchangeRateCollection $exchangeRates
     * @return Result
     */
    public function setExchangeRates(ExchangeRateCollection $exchangeRates): Result
    {
        $this->exchangeRates = $exchangeRates;
        return $this;
    }

    /**
     * @return array
     */
    public function getValidationErrors(): array
    {
        return $this->validationErrors;
    }

    /**
     * @param array $validationErrors
     * @return Result
     */
    public function setValidationErrors(array $validationErrors): Result
    {
        $this->validationErrors = $validationErrors;
        return $this;
    }

    public function hasValidationErrors(): bool
    {
        return count($this->validationErrors);
    }
}
