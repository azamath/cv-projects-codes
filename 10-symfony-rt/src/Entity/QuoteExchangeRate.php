<?php

namespace App\Entity;

use App\Repository\QuoteExchangeRateRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: QuoteExchangeRateRepository::class)]
#[ORM\Table(name: 'quotes_exchange_rates')]
class QuoteExchangeRate implements ExchangeRateInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $quoteExchangeRateId;

    #[ORM\Column(type: 'integer')]
    private $quoteId;

    #[ORM\Column(type: 'string', length: 16)]
    #[Assert\Length(max: 16)]
    #[Assert\Currency]
    private $currencyCode;

    #[ORM\Column(type: 'float')]
    private $conversionRate;

    #[ORM\ManyToOne(targetEntity: Quote::class, inversedBy: 'exchangeRates')]
    #[ORM\JoinColumn(name: 'quoteId', referencedColumnName: 'quoteId', nullable: false)]
    private $quote;

    public function getQuoteExchangeRateId(): ?int
    {
        return $this->quoteExchangeRateId;
    }

    public function getQuoteId(): ?int
    {
        return $this->quoteId;
    }

    public function setQuoteId(int $quoteId): self
    {
        $this->quoteId = $quoteId;

        return $this;
    }

    public function getCurrencyCode(): string
    {
        return $this->currencyCode;
    }

    public function setCurrencyCode(string $currencyCode): self
    {
        $this->currencyCode = $currencyCode;

        return $this;
    }

    public function getConversionRate(): float
    {
        return $this->conversionRate;
    }

    public function setConversionRate(float $conversionRate): self
    {
        $this->conversionRate = $conversionRate;

        return $this;
    }

    public function getQuote(): ?Quote
    {
        return $this->quote;
    }

    public function setQuote(?Quote $quote): self
    {
        $this->quote = $quote;

        return $this;
    }

    public function getRate(): float
    {
        return $this->getConversionRate();
    }
}
