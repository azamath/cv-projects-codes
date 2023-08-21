<?php

namespace App\Entity;

use App\Repository\QuoteCompanyRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: QuoteCompanyRepository::class)]
#[ORM\Table(name: 'quotes_companies')]
class QuoteCompany
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $quoteCompanyId;

    #[ORM\Column(type: 'integer')]
    private $quoteId;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    #[Assert\Length(max: 255)]
    private $invoiceCustomerReference;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    #[Assert\Length(max: 255)]
    private $invoiceEmailAddress;

    #[ORM\Column(type: 'string', length: 100, nullable: true)]
    #[Assert\Length(max: 100)]
    private $invoicePhoneNo;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    #[Assert\Length(max: 255)]
    private $invoiceAddress;

    #[ORM\Column(type: 'string', length: 100, nullable: true)]
    #[Assert\Length(max: 100)]
    private $invoiceZip;

    #[ORM\Column(type: 'string', length: 100, nullable: true)]
    #[Assert\Length(max: 100)]
    private $invoiceCity;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    #[Assert\Length(max: 255)]
    private $deliveryCustomerReference;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    #[Assert\Length(max: 255)]
    private $deliveryEmailAddress;

    #[ORM\Column(type: 'string', length: 100, nullable: true)]
    #[Assert\Length(max: 100)]
    private $deliveryPhoneNo;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    #[Assert\Length(max: 255)]
    private $deliveryAddress;

    #[ORM\Column(type: 'string', length: 100, nullable: true)]
    #[Assert\Length(max: 100)]
    private $deliveryZip;

    #[ORM\Column(type: 'string', length: 100, nullable: true)]
    #[Assert\Length(max: 100)]
    private $deliveryCity;

    #[ORM\Column(type: 'text')]
    private $organizationNumber = '';

    #[ORM\OneToOne(targetEntity: Quote::class, inversedBy: 'quoteCompany', cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(name: 'quoteId', referencedColumnName: 'quoteId', nullable: false)]
    private $quote;

    public function getQuoteCompanyId(): ?int
    {
        return $this->quoteCompanyId;
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

    public function getInvoiceCustomerReference(): ?string
    {
        return $this->invoiceCustomerReference;
    }

    public function setInvoiceCustomerReference(?string $invoiceCustomerReference): self
    {
        $this->invoiceCustomerReference = $invoiceCustomerReference;

        return $this;
    }

    public function getInvoiceEmailAddress(): ?string
    {
        return $this->invoiceEmailAddress;
    }

    public function setInvoiceEmailAddress(?string $invoiceEmailAddress): self
    {
        $this->invoiceEmailAddress = $invoiceEmailAddress;

        return $this;
    }

    public function getInvoicePhoneNo(): ?string
    {
        return $this->invoicePhoneNo;
    }

    public function setInvoicePhoneNo(?string $invoicePhoneNo): self
    {
        $this->invoicePhoneNo = $invoicePhoneNo;

        return $this;
    }

    public function getInvoiceAddress(): ?string
    {
        return $this->invoiceAddress;
    }

    public function setInvoiceAddress(?string $invoiceAddress): self
    {
        $this->invoiceAddress = $invoiceAddress;

        return $this;
    }

    public function getInvoiceZip(): ?string
    {
        return $this->invoiceZip;
    }

    public function setInvoiceZip(?string $invoiceZip): self
    {
        $this->invoiceZip = $invoiceZip;

        return $this;
    }

    public function getInvoiceCity(): ?string
    {
        return $this->invoiceCity;
    }

    public function setInvoiceCity(?string $invoiceCity): self
    {
        $this->invoiceCity = $invoiceCity;

        return $this;
    }

    public function getDeliveryCustomerReference(): ?string
    {
        return $this->deliveryCustomerReference;
    }

    public function setDeliveryCustomerReference(?string $deliveryCustomerReference): self
    {
        $this->deliveryCustomerReference = $deliveryCustomerReference;

        return $this;
    }

    public function getDeliveryEmailAddress(): ?string
    {
        return $this->deliveryEmailAddress;
    }

    public function setDeliveryEmailAddress(?string $deliveryEmailAddress): self
    {
        $this->deliveryEmailAddress = $deliveryEmailAddress;

        return $this;
    }

    public function getDeliveryPhoneNo(): ?string
    {
        return $this->deliveryPhoneNo;
    }

    public function setDeliveryPhoneNo(?string $deliveryPhoneNo): self
    {
        $this->deliveryPhoneNo = $deliveryPhoneNo;

        return $this;
    }

    public function getDeliveryAddress(): ?string
    {
        return $this->deliveryAddress;
    }

    public function setDeliveryAddress(?string $deliveryAddress): self
    {
        $this->deliveryAddress = $deliveryAddress;

        return $this;
    }

    public function getDeliveryZip(): ?string
    {
        return $this->deliveryZip;
    }

    public function setDeliveryZip(?string $deliveryZip): self
    {
        $this->deliveryZip = $deliveryZip;

        return $this;
    }

    public function getDeliveryCity(): ?string
    {
        return $this->deliveryCity;
    }

    public function setDeliveryCity(?string $deliveryCity): self
    {
        $this->deliveryCity = $deliveryCity;

        return $this;
    }

    public function getOrganizationNumber(): ?string
    {
        return $this->organizationNumber;
    }

    public function setOrganizationNumber(?string $organizationNumber): self
    {
        $this->organizationNumber = $organizationNumber;

        return $this;
    }

    public function getQuote(): ?Quote
    {
        return $this->quote;
    }

    public function setQuote(Quote $quote): self
    {
        $this->quote = $quote;

        return $this;
    }
}
