<?php

namespace App\Entity;

use App\Repository\SigningMetaRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SigningMetaRepository::class)]
#[ORM\Table(name: 'signings_meta')]
class SigningMeta
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $signingMetaId;

    #[ORM\Column(type: 'integer')]
    private $signingId;

    #[ORM\Column(type: 'integer')]
    private $quoteId;

    #[ORM\Column(type: 'integer', nullable: true)]
    private $distributorId;

    #[ORM\Column(type: 'integer')]
    private $resellerId;

    #[ORM\Column(type: 'integer')]
    private $vendorId;

    #[ORM\Column(type: 'integer')]
    private $endcustomerId;

    #[ORM\Column(type: 'boolean')]
    private $transmitted = false;

    #[ORM\Column(type: 'date', nullable: true)]
    private $transmissionDate;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private $reminderAttention;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private $reminderTarget;

    #[ORM\OneToOne(inversedBy: 'signingMeta', cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(name: 'signingId', referencedColumnName: 'signingId', nullable: false)]
    private ?Signing $signing = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'quoteId', referencedColumnName: 'quoteId', nullable: false)]
    private ?Quote $quote = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'distributorId', referencedColumnName: 'companyId')]
    private ?Company $distributor = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'resellerId', referencedColumnName: 'companyId', nullable: false)]
    private ?Company $reseller = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'vendorId', referencedColumnName: 'companyId', nullable: false)]
    private ?Company $vendor = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'endcustomerId', referencedColumnName: 'companyId', nullable: false)]
    private ?Company $endCustomer = null;

    public function getSigningMetaId(): ?int
    {
        return $this->signingMetaId;
    }

    public function getSigningId(): ?int
    {
        return $this->signingId;
    }

    public function setSigningId(int $signingId): self
    {
        $this->signingId = $signingId;

        return $this;
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

    public function getDistributorId(): ?int
    {
        return $this->distributorId;
    }

    public function setDistributorId(?int $distributorId): self
    {
        $this->distributorId = $distributorId;

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

    public function getVendorId(): ?int
    {
        return $this->vendorId;
    }

    public function setVendorId(int $vendorId): self
    {
        $this->vendorId = $vendorId;

        return $this;
    }

    public function getEndcustomerId(): ?int
    {
        return $this->endcustomerId;
    }

    public function setEndcustomerId(int $endcustomerId): self
    {
        $this->endcustomerId = $endcustomerId;

        return $this;
    }

    public function getTransmitted(): ?bool
    {
        return $this->transmitted;
    }

    public function setTransmitted(bool $transmitted): self
    {
        $this->transmitted = $transmitted;

        return $this;
    }

    public function getTransmissionDate(): ?\DateTimeInterface
    {
        return $this->transmissionDate;
    }

    public function setTransmissionDate(?\DateTimeInterface $transmissionDate): self
    {
        $this->transmissionDate = $transmissionDate;

        return $this;
    }

    public function getReminderAttention(): ?\DateTimeInterface
    {
        return $this->reminderAttention;
    }

    public function setReminderAttention(?\DateTimeInterface $reminderAttention): self
    {
        $this->reminderAttention = $reminderAttention;

        return $this;
    }

    public function getReminderTarget(): ?\DateTimeInterface
    {
        return $this->reminderTarget;
    }

    public function setReminderTarget(?\DateTimeInterface $reminderTarget): self
    {
        $this->reminderTarget = $reminderTarget;

        return $this;
    }

    public function getSigning(): ?Signing
    {
        return $this->signing;
    }

    public function setSigning(Signing $signing): self
    {
        $this->signing = $signing;

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

    public function getDistributor(): ?Company
    {
        return $this->distributor;
    }

    public function setDistributor(?Company $distributor): self
    {
        $this->distributor = $distributor;

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

    public function getVendor(): ?Company
    {
        return $this->vendor;
    }

    public function setVendor(?Company $vendor): self
    {
        $this->vendor = $vendor;

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
}
