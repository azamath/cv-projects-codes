<?php
/**
 * Author: Azamat Holmeer
 */

namespace App\Handler\ImportQuote;

use App\Dto\Collection\ExchangeRateCollection;
use App\Dto\ImportQuote\Result;
use App\Entity\Company;
use App\Entity\Quote;
use App\Entity\QuoteCompany;
use App\Entity\QuoteExchangeRate;
use App\Entity\QuoteProduct;
use App\Enum\EMarginCalculation;
use App\Enum\EMarginValueType;
use Doctrine\Common\Collections\ArrayCollection;
use function date_create;

class GenericMapper
{
    /**
     * Gets input context and runs it over different mappers and return a mapped result
     *
     * @param \App\Dto\ImportQuote\Quote $data
     * @return Result
     */
    public function map(\App\Dto\ImportQuote\Quote $data): Result
    {
        $result = new Result();
        $result->setEndCustomer($this->mapEndCustomer($data));
        $result->setQuote($this->mapQuote($data));
        $result->setQuoteCompany($this->mapCompanies($data));
        $result->setProducts($this->mapProducts($data));
        $result->setExchangeRates($this->mapExchangeRates($data));

        return $result;
    }

    protected function mapEndCustomer(\App\Dto\ImportQuote\Quote $data): Company
    {
        $endCustomer = new Company();
        $endCustomer->setName($data->endCustomer->name);
        $endCustomer->setEmail($data->endCustomer->email);
        $endCustomer->setTelephone($data->endCustomer->phone);

        return $endCustomer;
    }

    protected function mapQuote(\App\Dto\ImportQuote\Quote $data): Quote
    {
        $quote = new Quote();
        $quote->setVendorName($data->vendorName ?: '');
        $quote->setQuoteNumber($data->quoteNumber);
        $quote->setOriginQuoteNumber($data->quoteNumber);
        $quote->setSimpleQuoteNumber(substr($data->quoteNumber, 0, 16));
        $quote->setOriginCurrencyCode($data->currencyCode);
        $quote->setExpirationDate($data->expirationDate);
        $quote->setSupportExpirationDate($data->supportExpirationDate);
        $quote->setAccountNumber($data->endCustomer->accountNumber);
        $quote->setOrderable($data->orderable);
        $quote->setDealId($data->dealId);
        if ($data->solutionID) {
            $quote->setMetaKey('solutionID', $data->solutionID);
        }

        return $quote;
    }

    protected function mapProducts(\App\Dto\ImportQuote\Quote $data): ArrayCollection
    {
        $products = new ArrayCollection();

        foreach ($data->products as $productData) {
            $quoteProduct = new QuoteProduct();
            $quoteProduct->setSku($productData->sku);
            $quoteProduct->setName($productData->name);
            $quoteProduct->setDescription($productData->description ?? '');
            $quoteProduct->setLicenseId($productData->licenseNumber ?? '');
            $quoteProduct->setProductGroup('');

            $this->mapProductPrices($quoteProduct, $productData);

            $quoteProduct->setStartDate($productData->startDate);
            $quoteProduct->setEndDate($productData->endDate);
            if ($quoteProduct->getStartDate() && $quoteProduct->getEndDate()) {
                $interval = date_create($quoteProduct->getEndDate())->diff(date_create($quoteProduct->getStartDate()));
                $quoteProduct->setNumberOfDays((int)$interval->format('%a'));
            }

            $quoteProduct->setMarginSelfValue(0);
            $quoteProduct->setMarginSelfValueType(EMarginValueType::PERCENTAGE);
            $quoteProduct->setMarginSelfCalculationType(EMarginCalculation::MARGIN);

            $products->add($quoteProduct);
        }

        return $products;
    }

    protected function mapCompanies(\App\Dto\ImportQuote\Quote $data): QuoteCompany
    {
        // Create Quote Company
        $quoteCompany = new QuoteCompany();

        if ($data->invoiceAddress) {
            $quoteCompany->setInvoiceCustomerReference((string)$data->invoiceAddress->referenceName);
            $quoteCompany->setInvoiceEmailAddress((string)$data->invoiceAddress->email);
            $quoteCompany->setInvoicePhoneNo((string)$data->invoiceAddress->phone);
            $quoteCompany->setInvoiceAddress((string)$data->invoiceAddress->address);
            $quoteCompany->setInvoiceZip((string)$data->invoiceAddress->zip);
            $quoteCompany->setInvoiceCity((string)$data->invoiceAddress->city);
        }

        if ($data->shippingAddress) {
            $quoteCompany->setDeliveryCustomerReference((string)$data->shippingAddress->referenceName);
            $quoteCompany->setDeliveryEmailAddress((string)$data->shippingAddress->email);
            $quoteCompany->setDeliveryPhoneNo((string)$data->shippingAddress->phone);
            $quoteCompany->setDeliveryAddress((string)$data->shippingAddress->address);
            $quoteCompany->setDeliveryZip((string)$data->shippingAddress->zip);
            $quoteCompany->setDeliveryCity((string)$data->shippingAddress->city);
        }

        return $quoteCompany;
    }

    protected function mapExchangeRates(\App\Dto\ImportQuote\Quote $data): ExchangeRateCollection
    {
        $exchangeRates = new ExchangeRateCollection();

        foreach ($data->exchangeRates as $data) {
            $exchangeRate = new QuoteExchangeRate();
            $exchangeRate->setCurrencyCode($data->currencyCode);
            $exchangeRate->setConversionRate((float)$data->rate);
            $exchangeRates->add($exchangeRate);
        }

        return $exchangeRates;
    }

    protected function mapProductPrices(QuoteProduct $quoteProduct, \App\Dto\ImportQuote\Product $productData): void
    {
        $quantity = $productData->quantity;
        $price = $productData->price;
        $totalPrice = $productData->totalPrice;
        if (null === $price && null !== $totalPrice && null !== $quantity) {
            $price = $totalPrice / $quantity;
        }
        if (null === $quantity && null !== $totalPrice && null !== $price) {
            $quantity = round($totalPrice / $price);
        }
        $quoteProduct->setQuantity((int)$quantity);
        $quoteProduct->setPrice((float)$price);
        $quoteProduct->setSinglePrice((float)$price);
        $quoteProduct->setAnnualList((float)$productData->annualList);
        $quoteProduct->setExtendedPrice((float)$productData->extendedPrice);
        $quoteProduct->setReinstatementFee((float)$productData->reinstatementFee);
        $quoteProduct->setDiscount((float)$productData->discount);
        if (!is_null($productData->rawData)) {
            $quoteProduct->setRawData((array)$productData->rawData);
        }
    }
}
