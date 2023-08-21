<?php

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiFilter;
use ApiPlatform\Core\Annotation\ApiResource;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\BooleanFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\DateFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\OrderFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Core\Serializer\Filter\PropertyFilter;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Signing
 */
#[ApiResource(
    collectionOperations: [
        'get',
    ],
    itemOperations: [
        'get'
    ],
    attributes: ["security" => "is_granted('IS_AUTHENTICATED_FULLY')"],
)]
#[ApiFilter(PropertyFilter::class)]
#[ORM\Entity(repositoryClass: \App\Repository\SigningRepository::class)]
#[ORM\Table(name: 'signings')]
#[ORM\HasLifecycleCallbacks]
class Signing implements \Stringable
{
    use SetsCreatedDate;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column()]
    #[ApiFilter(SearchFilter::class, strategy: SearchFilter::STRATEGY_EXACT)]
    private ?int $signingId;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: false)]
    #[ApiFilter(OrderFilter::class)]
    #[ApiFilter(DateFilter::class)]
    private ?\DateTimeInterface $createdDate;

    #[ORM\Column(nullable: false)]
    #[ApiFilter(SearchFilter::class, strategy: SearchFilter::STRATEGY_EXACT)]
    private ?int $createdUserId;

    #[ORM\Column(length: 255, nullable: false)]
    #[ApiFilter(SearchFilter::class, strategy: 'i' . SearchFilter::STRATEGY_PARTIAL)]
    private ?string $quoteName;

    #[ORM\Column(length: 255, nullable: false)]
    #[ApiFilter(SearchFilter::class, strategy: 'i' . SearchFilter::STRATEGY_PARTIAL)]
    private ?string $vendorName;

    #[ORM\Column(length: 255, nullable: false)]
    #[ApiFilter(SearchFilter::class, strategy: 'i' . SearchFilter::STRATEGY_PARTIAL)]
    private ?string $resellerName;

    #[ORM\Column(length: 255, nullable: false)]
    #[ApiFilter(SearchFilter::class, strategy: 'i' . SearchFilter::STRATEGY_PARTIAL)]
    private ?string $endcustomerName;

    #[ORM\Column(length: 255, nullable: false)]
    #[ApiFilter(SearchFilter::class, strategy: 'i' . SearchFilter::STRATEGY_PARTIAL)]
    private ?string $createdUserName;

    #[ORM\Column(nullable: true)]
    private bool $deleted = false;

    #[ORM\Column(length: 255, nullable: false)]
    #[ApiFilter(SearchFilter::class, strategy: 'i' . SearchFilter::STRATEGY_PARTIAL)]
    private ?string $quoteNumber;

    #[ORM\Column(length: 16, nullable: true)]
    #[ApiFilter(SearchFilter::class, strategy: 'i' . SearchFilter::STRATEGY_PARTIAL)]
    private ?string $simpleQuoteNumber = null;

    #[ORM\Column(length: 16, nullable: true)]
    #[ApiFilter(SearchFilter::class, strategy: 'i' . SearchFilter::STRATEGY_PARTIAL)]
    private ?string $accountNumber = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: false)]
    #[ApiFilter(OrderFilter::class)]
    #[ApiFilter(DateFilter::class)]
    private ?\DateTimeInterface $expirationDate;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: false)]
    #[ApiFilter(OrderFilter::class)]
    #[ApiFilter(DateFilter::class)]
    private ?\DateTimeInterface $supportExpirationDate;

    #[ORM\Column(nullable: false)]
    #[ApiFilter(SearchFilter::class, strategy: SearchFilter::STRATEGY_EXACT)]
    private ?int $resellerType;

    #[ORM\Column(length: 16, nullable: true)]
    #[ApiFilter(SearchFilter::class, strategy: SearchFilter::STRATEGY_EXACT)]
    private ?string $originCurrencyCode = null;

    #[ORM\Column(length: 255, nullable: false)]
    #[ApiFilter(SearchFilter::class, strategy: 'i' . SearchFilter::STRATEGY_PARTIAL)]
    private ?string $distributorName;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $additionalInformation = null;

    #[ORM\Column(length: 255, nullable: false)]
    private ?string $originQuoteNumber;

    #[ORM\Column(nullable: true)]
    private ?int $quoteDuration = null;

    #[ORM\Column(length: 16, nullable: true)]
    #[ApiFilter(SearchFilter::class, strategy: SearchFilter::STRATEGY_EXACT)]
    private ?string $outputCurrencyCode = null;

    #[ORM\Column(nullable: true)]
    #[ApiFilter(BooleanFilter::class)]
    private ?bool $orderable = null;

    #[ORM\Column(length: 60, nullable: true)]
    #[ApiFilter(SearchFilter::class, strategy: 'i' . SearchFilter::STRATEGY_PARTIAL)]
    private ?string $dealId = null;

    #[ORM\Column(nullable: true)]
    private ?array $meta = null;

    #[ORM\OneToOne(mappedBy: 'signing', cascade: ['persist', 'remove'])]
    private ?SigningState $signingState = null;

    #[ORM\OneToOne(mappedBy: 'signing', cascade: ['persist', 'remove'])]
    private ?SigningMeta $signingMeta = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'createdUserId', referencedColumnName: 'userId', nullable: false)]
    private ?User $createdUser = null;

    public function __toString(): string
    {
        $id = $this->signingId ?? 'NEW';
        return "Signing.{$id}";
    }

    public function getSigningId(): ?int
    {
        return $this->signingId;
    }

    public function getCreatedDate(): ?\DateTimeInterface
    {
        return $this->createdDate;
    }

    public function setCreatedDate(\DateTimeInterface $createdDate): self
    {
        $this->createdDate = $createdDate;

        return $this;
    }

    public function getCreatedUserId(): ?int
    {
        return $this->createdUserId;
    }

    public function setCreatedUserId(int $createdUserId): self
    {
        $this->createdUserId = $createdUserId;

        return $this;
    }

    public function getQuoteName(): ?string
    {
        return $this->quoteName;
    }

    public function setQuoteName(string $quoteName): self
    {
        $this->quoteName = $quoteName;

        return $this;
    }

    public function getVendorName(): ?string
    {
        return $this->vendorName;
    }

    public function setVendorName(string $vendorName): self
    {
        $this->vendorName = $vendorName;

        return $this;
    }

    public function getResellerName(): ?string
    {
        return $this->resellerName;
    }

    public function setResellerName(string $resellerName): self
    {
        $this->resellerName = $resellerName;

        return $this;
    }

    public function getEndcustomerName(): ?string
    {
        return $this->endcustomerName;
    }

    public function setEndcustomerName(string $endcustomerName): self
    {
        $this->endcustomerName = $endcustomerName;

        return $this;
    }

    public function getCreatedUserName(): ?string
    {
        return $this->createdUserName;
    }

    public function setCreatedUserName(string $createdUserName): self
    {
        $this->createdUserName = $createdUserName;

        return $this;
    }

    public function getDeleted(): ?bool
    {
        return $this->deleted;
    }

    public function setDeleted(?bool $deleted): self
    {
        $this->deleted = $deleted;

        return $this;
    }

    public function getQuoteNumber(): ?string
    {
        return $this->quoteNumber;
    }

    public function setQuoteNumber(string $quoteNumber): self
    {
        $this->quoteNumber = $quoteNumber;

        return $this;
    }

    public function getSimpleQuoteNumber(): ?string
    {
        return $this->simpleQuoteNumber;
    }

    public function setSimpleQuoteNumber(?string $simpleQuoteNumber): self
    {
        $this->simpleQuoteNumber = $simpleQuoteNumber;

        return $this;
    }

    public function getAccountNumber(): ?string
    {
        return $this->accountNumber;
    }

    public function setAccountNumber(?string $accountNumber): self
    {
        $this->accountNumber = $accountNumber;

        return $this;
    }

    public function getExpirationDate(): ?\DateTimeInterface
    {
        return $this->expirationDate;
    }

    public function setExpirationDate(\DateTimeInterface $expirationDate): self
    {
        $this->expirationDate = $expirationDate;

        return $this;
    }

    public function getSupportExpirationDate(): ?\DateTimeInterface
    {
        return $this->supportExpirationDate;
    }

    public function setSupportExpirationDate(\DateTimeInterface $supportExpirationDate): self
    {
        $this->supportExpirationDate = $supportExpirationDate;

        return $this;
    }

    public function getResellerType(): ?int
    {
        return $this->resellerType;
    }

    public function setResellerType(int $resellerType): self
    {
        $this->resellerType = $resellerType;

        return $this;
    }

    public function getOriginCurrencyCode(): ?string
    {
        return $this->originCurrencyCode;
    }

    public function setOriginCurrencyCode(?string $originCurrencyCode): self
    {
        $this->originCurrencyCode = $originCurrencyCode;

        return $this;
    }

    public function getDistributorName(): ?string
    {
        return $this->distributorName;
    }

    public function setDistributorName(string $distributorName): self
    {
        $this->distributorName = $distributorName;

        return $this;
    }

    public function getAdditionalInformation(): ?string
    {
        return $this->additionalInformation;
    }

    public function setAdditionalInformation(?string $additionalInformation): self
    {
        $this->additionalInformation = $additionalInformation;

        return $this;
    }

    public function getOriginQuoteNumber(): ?string
    {
        return $this->originQuoteNumber;
    }

    public function setOriginQuoteNumber(string $originQuoteNumber): self
    {
        $this->originQuoteNumber = $originQuoteNumber;

        return $this;
    }

    public function getQuoteDuration(): ?int
    {
        return $this->quoteDuration;
    }

    public function setQuoteDuration(?int $quoteDuration): self
    {
        $this->quoteDuration = $quoteDuration;

        return $this;
    }

    public function getOutputCurrencyCode(): ?string
    {
        return $this->outputCurrencyCode;
    }

    public function setOutputCurrencyCode(?string $outputCurrencyCode): self
    {
        $this->outputCurrencyCode = $outputCurrencyCode;

        return $this;
    }

    public function getSigningState(): ?SigningState
    {
        return $this->signingState;
    }

    public function setSigningState(?SigningState $signingState): self
    {
        // set the owning side of the relation if necessary
        if ($signingState->getSigning() !== $this) {
            $signingState->setSigning($this);
        }

        $this->signingState = $signingState;

        return $this;
    }

    public function isOrderable(): ?bool
    {
        return $this->orderable;
    }

    public function setOrderable(?bool $orderable): self
    {
        $this->orderable = $orderable;

        return $this;
    }

    public function getDealId(): ?string
    {
        return $this->dealId;
    }

    public function setDealId(?string $dealId): self
    {
        $this->dealId = $dealId;

        return $this;
    }

    public function getMeta(): ?array
    {
        return $this->meta;
    }

    public function setMeta(?array $meta): self
    {
        $this->meta = $meta;

        return $this;
    }

    public function getMetaKey(string $key): mixed
    {
        return $this->meta[$key] ?? null;
    }

    public function setMetaKey(string $key, mixed $value): self
    {
        $this->meta[$key] = $value;

        return $this;
    }

    public function getSigningMeta(): ?SigningMeta
    {
        return $this->signingMeta;
    }

    public function setSigningMeta(SigningMeta $signingMeta): self
    {
        // set the owning side of the relation if necessary
        if ($signingMeta->getSigning() !== $this) {
            $signingMeta->setSigning($this);
        }

        $this->signingMeta = $signingMeta;

        return $this;
    }

    public function getCreatedUser(): ?User
    {
        return $this->createdUser;
    }

    public function setCreatedUser(?User $createdUser): self
    {
        $this->createdUser = $createdUser;
        $this->setCreatedUserName($createdUser->getUsername());

        return $this;
    }
}
