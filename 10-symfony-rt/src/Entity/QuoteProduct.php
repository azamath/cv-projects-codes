<?php

namespace App\Entity;

use App\Enum\EMarginCalculation;
use App\Enum\EMarginValueType;
use App\Repository\QuoteProductRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: QuoteProductRepository::class)]
#[ORM\Table(name: 'quotes_products')]
class QuoteProduct
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $quoteProductId;

    #[ORM\Column(type: 'integer')]
    private $quoteId;

    #[ORM\Column(type: 'integer', nullable: true)]
    private $productId;

    #[ORM\Column(type: 'integer', nullable: true)]
    #[Assert\NotNull]
    private $quantity;

    #[ORM\Column(type: 'float', nullable: true)]
    private $price;

    #[ORM\Column(type: 'string', length: 100, nullable: true)]
    #[Assert\NotNull]
    #[Assert\Length(max: 100)]
    private $licenseId;

    #[ORM\Column(type: 'float', nullable: true)]
    private $annualList;

    #[ORM\Column(type: 'string', length: 100, nullable: true)]
    #[Assert\Length(max: 100)]
    #[Assert\DateTime(format: "Y-m-d H:i:s")]
    private $startDate;

    #[ORM\Column(type: 'string', length: 100, nullable: true)]
    #[Assert\Length(max: 100)]
    #[Assert\DateTime(format: "Y-m-d H:i:s")]
    private $endDate;

    #[ORM\Column(type: 'integer', nullable: true)]
    private $numberOfDays;

    #[ORM\Column(type: 'float', nullable: true)]
    private $extendedPrice;

    #[ORM\Column(type: 'float', nullable: true)]
    private $reinstatementFee;

    #[ORM\Column(type: 'float', nullable: true)]
    private $discount;

    #[ORM\Column(type: 'float', nullable: true)]
    #[Assert\NotNull]
    private $singlePrice;

    #[ORM\Column(type: 'string', length: 128, nullable: true)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 128)]
    private $sku;

    #[ORM\Column(type: 'string', length: 255)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    private $name;

    #[ORM\Column(type: 'text')]
    #[Assert\NotNull]
    private $description;

    #[ORM\Column(type: 'string', length: 255)]
    #[Assert\NotNull]
    #[Assert\Length(max: 255)]
    private $productGroup;

    #[ORM\Column(type: 'float')]
    #[Assert\NotNull]
    private $marginSelfValue;

    #[ORM\Column(type: 'integer')]
    #[Assert\NotNull]
    private $marginSelfValueType;

    #[ORM\Column(type: 'integer')]
    #[Assert\NotNull]
    private $marginSelfCalculationType;

    #[ORM\Column(type: 'integer')]
    private $priceBehaviour = 0;

    #[ORM\Column(type: 'text', nullable: true)]
    private $rawData;

    #[ORM\Column(type: 'float', nullable: true)]
    private $expectedOutputPrice;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    #[Assert\Length(max: 255)]
    private $productHeadline;

    #[ORM\ManyToOne(targetEntity: Quote::class, inversedBy: 'quoteProducts')]
    #[ORM\JoinColumn(name: 'quoteId', referencedColumnName: 'quoteId', nullable: false)]
    private $quote;

    public function getQuoteProductId(): ?int
    {
        return $this->quoteProductId;
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

    public function getProductId(): ?int
    {
        return $this->productId;
    }

    public function setProductId(?int $productId): self
    {
        $this->productId = $productId;

        return $this;
    }

    public function getQuantity(): ?int
    {
        return $this->quantity;
    }

    public function setQuantity(?int $quantity): self
    {
        $this->quantity = $quantity;

        return $this;
    }

    public function getPrice(): ?float
    {
        return $this->price;
    }

    public function setPrice(?float $price): self
    {
        $this->price = $price;

        return $this;
    }

    public function getLicenseId(): ?string
    {
        return $this->licenseId;
    }

    public function setLicenseId(?string $licenseId): self
    {
        $this->licenseId = $licenseId;

        return $this;
    }

    public function getAnnualList(): ?float
    {
        return $this->annualList;
    }

    public function setAnnualList(?float $annualList): self
    {
        $this->annualList = $annualList;

        return $this;
    }

    public function getStartDate(): ?string
    {
        return $this->startDate;
    }

    public function setStartDate(?string $startDate): self
    {
        $this->startDate = $startDate;

        return $this;
    }

    public function getEndDate(): ?string
    {
        return $this->endDate;
    }

    public function setEndDate(?string $endDate): self
    {
        $this->endDate = $endDate;

        return $this;
    }

    public function getNumberOfDays(): ?int
    {
        return $this->numberOfDays;
    }

    public function setNumberOfDays(?int $numberOfDays): self
    {
        $this->numberOfDays = $numberOfDays;

        return $this;
    }

    public function getExtendedPrice(): ?float
    {
        return $this->extendedPrice;
    }

    public function setExtendedPrice(?float $extendedPrice): self
    {
        $this->extendedPrice = $extendedPrice;

        return $this;
    }

    public function getReinstatementFee(): ?float
    {
        return $this->reinstatementFee;
    }

    public function setReinstatementFee(?float $reinstatementFee): self
    {
        $this->reinstatementFee = $reinstatementFee;

        return $this;
    }

    public function getDiscount(): ?float
    {
        return $this->discount;
    }

    public function setDiscount(?float $discount): self
    {
        $this->discount = $discount;

        return $this;
    }

    public function getSinglePrice(): ?float
    {
        return $this->singlePrice;
    }

    public function setSinglePrice(?float $singlePrice): self
    {
        $this->singlePrice = $singlePrice;

        return $this;
    }

    public function getSku(): ?string
    {
        return $this->sku;
    }

    public function setSku(?string $sku): self
    {
        $this->sku = $sku;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getProductGroup(): ?string
    {
        return $this->productGroup;
    }

    public function setProductGroup(string $productGroup): self
    {
        $this->productGroup = $productGroup;

        return $this;
    }

    public function getMarginSelfValue(): ?float
    {
        return $this->marginSelfValue;
    }

    public function setMarginSelfValue(float $marginSelfValue): self
    {
        $this->marginSelfValue = $marginSelfValue;

        return $this;
    }

    public function getMarginSelfValueType(): EMarginValueType
    {
        return EMarginValueType::from($this->marginSelfValueType);
    }

    public function setMarginSelfValueType(EMarginValueType $marginSelfValueType): self
    {
        $this->marginSelfValueType = $marginSelfValueType->value;

        return $this;
    }

    public function getMarginSelfCalculationType(): EMarginCalculation
    {
        return EMarginCalculation::from($this->marginSelfCalculationType);
    }

    public function setMarginSelfCalculationType(EMarginCalculation $marginSelfCalculationType): self
    {
        $this->marginSelfCalculationType = $marginSelfCalculationType->value;

        return $this;
    }

    public function getPriceBehaviour(): ?int
    {
        return $this->priceBehaviour;
    }

    public function setPriceBehaviour(int $priceBehaviour): self
    {
        $this->priceBehaviour = $priceBehaviour;

        return $this;
    }

    public function getRawData(): iterable
    {
        return is_string($this->rawData) ? \App\Services\Hstore::deserialize($this->rawData) : [];
    }

    public function setRawData(?iterable $rawData): self
    {
        $this->rawData = is_null($rawData) ? null : \App\Services\Hstore::serialize($rawData);

        return $this;
    }

    public function getExpectedOutputPrice(): ?float
    {
        return $this->expectedOutputPrice;
    }

    public function setExpectedOutputPrice(?float $expectedOutputPrice): self
    {
        $this->expectedOutputPrice = $expectedOutputPrice;

        return $this;
    }

    public function getProductHeadline(): ?string
    {
        return $this->productHeadline;
    }

    public function setProductHeadline(?string $productHeadline): self
    {
        $this->productHeadline = $productHeadline;

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
}
