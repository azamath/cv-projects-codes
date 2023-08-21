<?php

namespace App\Entity;

use App\Enum\ESigningState;
use App\Repository\QuoteRepository;
use DateTimeInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: QuoteRepository::class)]
#[ORM\Table(name: 'quotes')]
#[ORM\HasLifecycleCallbacks]
class Quote implements \Stringable
{
    use SetsCreatedDate;
    use SetsModifiedDate;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $quoteId;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    private ?string $quoteNumber;

    #[ORM\Column(nullable: true)]
    private ?int $endcustomerId = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?DateTimeInterface $createdDate;

    #[ORM\Column]
    private ?int $createdUserId;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\Length(max: 255)]
    private ?string $filename = null;

    #[ORM\Column(nullable: true)]
    private ?int $productCnt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?DateTimeInterface $modifiedDate;

    #[ORM\Column]
    private ?int $modifiedUserId;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    private ?string $name;

    #[ORM\Column]
    #[Assert\NotNull]
    private ?int $vendorId;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    private ?string $vendorName;

    #[ORM\Column]
    private bool $deleted = false;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    #[Assert\NotNull]
    private ?DateTimeInterface $expirationDate = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?DateTimeInterface $supportExpirationDate = null;

    #[ORM\Column(length: 16, nullable: true)]
    #[Assert\Length(max: 16)]
    private ?string $simpleQuoteNumber = null;

    #[ORM\Column(length: 16, nullable: true)]
    #[Assert\Length(max: 16)]
    private ?string $accountNumber = null;

    #[ORM\Column(length: 64, nullable: true)]
    #[Assert\Length(max: 64)]
    private ?string $transactionId = null;

    #[ORM\Column(nullable: true)]
    private ?bool $confirmed = null;

    #[ORM\Column(length: 16, nullable: true)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 16)]
    #[Assert\Currency]
    private ?string $originCurrencyCode = null;

    #[ORM\Column]
    #[Assert\NotNull]
    private ?int $originCompanyId;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $additionalInformation = null;

    #[ORM\Column]
    #[Assert\NotNull]
    private ?int $resellerId;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    private ?string $originQuoteNumber;

    #[ORM\Column(nullable: true)]
    private ?int $quoteDuration = null;

    #[ORM\Column(nullable: true)]
    private ?int $baseSigningId = null;

    #[ORM\Column(nullable: true)]
    private ?int $resolvedState = null;

    #[ORM\Column]
    private bool $stateSynced = false;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?DateTimeInterface $stateSyncDate = null;

    #[ORM\Column]
    private int $stateSyncTries = 0;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?DateTimeInterface $stateSyncTryDate = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?DateTimeInterface $stateSyncFailureNotifiedDate = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'vendorId', referencedColumnName: 'companyId', nullable: false)]
    private ?Company $vendor = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'originCompanyId', referencedColumnName: 'companyId', nullable: false)]
    private ?Company $originCompany = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'resellerId', referencedColumnName: 'companyId', nullable: false)]
    private ?Company $reseller = null;

    #[ORM\ManyToOne()]
    #[ORM\JoinColumn(name: 'endcustomerId', referencedColumnName: 'companyId', nullable: false)]
    private ?Company $endCustomer;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'createdUserId', referencedColumnName: 'userId', nullable: false)]
    private ?User $createdUser = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'modifiedUserId', referencedColumnName: 'userId', nullable: false)]
    private ?User $modifiedUser = null;

    #[ORM\OneToOne(mappedBy: 'quote', cascade: ['persist', 'remove'])]
    private ?QuoteCompany $quoteCompany;

    #[ORM\OneToMany(targetEntity: QuoteProduct::class, mappedBy: 'quote', cascade: ['persist', 'remove'])]
    private $quoteProducts;

    #[ORM\OneToMany(targetEntity: QuoteExchangeRate::class, mappedBy: 'quote', cascade: ['persist', 'remove'])]
    private $exchangeRates;

    #[ORM\Column(nullable: true)]
    private ?bool $orderable = null;

    #[ORM\Column(length: 60, nullable: true)]
    #[Assert\Length(max: 60)]
    private ?string $dealId = null;

    #[ORM\Column(nullable: true)]
    private ?array $meta = null;

    public function __construct()
    {
        $this->quoteProducts = new ArrayCollection();
        $this->exchangeRates = new ArrayCollection();
    }

    public function __toString(): string
    {
        $id = $this->quoteId ?? 'NEW';
        return "Quote.{$id}";
    }

    public function __clone(): void
    {
        if (!isset($this->quoteId)) {
            return;
        }

        $this->createdDate = null;
        $this->modifiedDate = null;

        if (isset($this->quoteCompany)) {
            $this->setQuoteCompany(clone $this->quoteCompany);
        }

        $quoteProducts = $this->quoteProducts;
        $this->quoteProducts = new ArrayCollection();
        foreach ($quoteProducts as $product) {
            $this->addQuoteProduct(clone $product);
        }

        $exchangeRates = $this->exchangeRates;
        $this->exchangeRates = new ArrayCollection();
        foreach ($exchangeRates as $exchangeRate) {
            $this->addExchangeRate(clone $exchangeRate);
        }
    }

    public function getQuoteId(): ?int
    {
        return $this->quoteId;
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

    public function getEndcustomerId(): ?int
    {
        return $this->endcustomerId;
    }

    public function setEndcustomerId(?int $endcustomerId): self
    {
        $this->endcustomerId = $endcustomerId;

        return $this;
    }

    public function getCreatedDate(): ?DateTimeInterface
    {
        return $this->createdDate;
    }

    public function setCreatedDate(DateTimeInterface $createdDate): self
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

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getFilename(): ?string
    {
        return $this->filename;
    }

    public function setFilename(?string $filename): self
    {
        $this->filename = $filename;

        return $this;
    }

    public function getProductCnt(): ?int
    {
        return $this->productCnt;
    }

    public function setProductCnt(?int $productCnt): self
    {
        $this->productCnt = $productCnt;

        return $this;
    }

    public function getModifiedDate(): ?DateTimeInterface
    {
        return $this->modifiedDate;
    }

    public function setModifiedDate(DateTimeInterface $modifiedDate): self
    {
        $this->modifiedDate = $modifiedDate;

        return $this;
    }

    public function getModifiedUserId(): ?int
    {
        return $this->modifiedUserId;
    }

    public function setModifiedUserId(int $modifiedUserId): self
    {
        $this->modifiedUserId = $modifiedUserId;

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

    public function getVendorId(): ?int
    {
        return $this->vendorId;
    }

    public function setVendorId(int $vendorId): self
    {
        $this->vendorId = $vendorId;

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

    public function getDeleted(): ?bool
    {
        return $this->deleted;
    }

    public function setDeleted(bool $deleted): self
    {
        $this->deleted = $deleted;

        return $this;
    }

    public function getExpirationDate(): ?DateTimeInterface
    {
        return $this->expirationDate;
    }

    public function setExpirationDate(?DateTimeInterface $expirationDate): self
    {
        $this->expirationDate = $expirationDate;

        return $this;
    }

    public function getSupportExpirationDate(): ?DateTimeInterface
    {
        return $this->supportExpirationDate;
    }

    public function setSupportExpirationDate(?DateTimeInterface $supportExpirationDate): self
    {
        $this->supportExpirationDate = $supportExpirationDate;

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

    public function getTransactionId(): ?string
    {
        return $this->transactionId;
    }

    public function setTransactionId(?string $transactionId): self
    {
        $this->transactionId = $transactionId;

        return $this;
    }

    public function getConfirmed(): ?bool
    {
        return $this->confirmed;
    }

    public function setConfirmed(?bool $confirmed): self
    {
        $this->confirmed = $confirmed;

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

    public function getOriginCompanyId(): ?int
    {
        return $this->originCompanyId;
    }

    public function setOriginCompanyId(int $originCompanyId): self
    {
        $this->originCompanyId = $originCompanyId;

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

    public function getResellerId(): ?int
    {
        return $this->resellerId;
    }

    public function setResellerId(int $resellerId): self
    {
        $this->resellerId = $resellerId;

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

    public function getBaseSigningId(): ?int
    {
        return $this->baseSigningId;
    }

    public function setBaseSigningId(?int $baseSigningId): self
    {
        $this->baseSigningId = $baseSigningId;

        return $this;
    }

    public function getResolvedState(): ?ESigningState
    {
        return !is_null($this->resolvedState) ? ESigningState::from($this->resolvedState) : null;
    }

    public function setResolvedState(?ESigningState $resolvedState): self
    {
        $this->resolvedState = $resolvedState?->value;

        return $this;
    }

    public function getStateSynced(): ?bool
    {
        return $this->stateSynced;
    }

    public function setStateSynced(bool $stateSynced): self
    {
        $this->stateSynced = $stateSynced;

        return $this;
    }

    public function getStateSyncDate(): ?DateTimeInterface
    {
        return $this->stateSyncDate;
    }

    public function setStateSyncDate(?DateTimeInterface $stateSyncDate): self
    {
        $this->stateSyncDate = $stateSyncDate;

        return $this;
    }

    public function getStateSyncTries(): int
    {
        return $this->stateSyncTries;
    }

    public function setStateSyncTries(int $stateSyncTries): self
    {
        $this->stateSyncTries = $stateSyncTries;

        return $this;
    }

    public function incrementStateSyncTries(int $number = 1): self
    {
        $this->stateSyncTries += $number;

        return $this;
    }

    public function getStateSyncTryDate(): ?DateTimeInterface
    {
        return $this->stateSyncTryDate;
    }

    public function setStateSyncTryDate(?DateTimeInterface $stateSyncTryDate): self
    {
        $this->stateSyncTryDate = $stateSyncTryDate;

        return $this;
    }

    public function getStateSyncFailureNotifiedDate(): ?DateTimeInterface
    {
        return $this->stateSyncFailureNotifiedDate;
    }

    public function setStateSyncFailureNotifiedDate(?DateTimeInterface $stateSyncFailureNotifiedDate): self
    {
        $this->stateSyncFailureNotifiedDate = $stateSyncFailureNotifiedDate;

        return $this;
    }

    public function getVendor(): ?Company
    {
        return $this->vendor;
    }

    public function setVendor(?Company $vendor): self
    {
        $this->vendor = $vendor;

        return $this;
    }

    public function getOriginCompany(): ?Company
    {
        return $this->originCompany;
    }

    public function setOriginCompany(?Company $originCompany): self
    {
        $this->originCompany = $originCompany;

        return $this;
    }

    public function getReseller(): ?Company
    {
        return $this->reseller;
    }

    public function setReseller(?Company $reseller): self
    {
        $this->reseller = $reseller;

        return $this;
    }

    public function getCreatedUser(): ?User
    {
        return $this->createdUser;
    }

    public function setCreatedUser(?User $createdUser): self
    {
        $this->createdUser = $createdUser;

        return $this;
    }

    public function getEndCustomer(): ?Company
    {
        return $this->endCustomer;
    }

    public function setEndCustomer(?Company $endCustomer): self
    {
        $this->endCustomer = $endCustomer;

        return $this;
    }

    public function getQuoteCompany(): ?QuoteCompany
    {
        return $this->quoteCompany;
    }

    public function setQuoteCompany(QuoteCompany $quoteCompany): self
    {
        // set the owning side of the relation if necessary
        if ($quoteCompany->getQuote() !== $this) {
            $quoteCompany->setQuote($this);
        }

        $this->quoteCompany = $quoteCompany;

        return $this;
    }

    /**
     * @return Collection|QuoteProduct[]
     */
    public function getQuoteProducts(): Collection
    {
        return $this->quoteProducts;
    }

    public function addQuoteProduct(QuoteProduct $quoteProduct): self
    {
        if (!$this->quoteProducts->contains($quoteProduct)) {
            $this->quoteProducts[] = $quoteProduct;
            $quoteProduct->setQuote($this);
        }

        return $this;
    }

    public function removeQuoteProduct(QuoteProduct $quoteProduct): self
    {
        if ($this->quoteProducts->removeElement($quoteProduct)) {
            // set the owning side to null (unless already changed)
            if ($quoteProduct->getQuote() === $this) {
                $quoteProduct->setQuote(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|QuoteExchangeRate[]
     */
    public function getExchangeRates(): Collection
    {
        return $this->exchangeRates;
    }

    public function addExchangeRate(QuoteExchangeRate $exchangeRate): self
    {
        if (!$this->exchangeRates->contains($exchangeRate)) {
            $this->exchangeRates[] = $exchangeRate;
            $exchangeRate->setQuote($this);
        }

        return $this;
    }

    public function removeExchangeRate(QuoteExchangeRate $exchangeRate): self
    {
        if ($this->exchangeRates->removeElement($exchangeRate)) {
            // set the owning side to null (unless already changed)
            if ($exchangeRate->getQuote() === $this) {
                $exchangeRate->setQuote(null);
            }
        }

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

    public function getModifiedUser(): ?User
    {
        return $this->modifiedUser;
    }

    public function setModifiedUser(?User $modifiedUser): self
    {
        $this->modifiedUser = $modifiedUser;

        return $this;
    }
}
