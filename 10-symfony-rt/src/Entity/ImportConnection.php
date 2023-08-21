<?php

namespace App\Entity;

use App\Repository\ImportConnectionRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ImportConnectionRepository::class)]
#[ORM\Table(name: 'import_connection')]
class ImportConnection
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $importConnectionId;

    #[ORM\Column(type: 'integer')]
    private $vendorId;

    #[ORM\Column(type: 'boolean', nullable: true)]
    private $enabledProductCatalogImport;

    #[ORM\Column(type: 'boolean', nullable: true)]
    private $enabledQuoteImport;

    #[ORM\Column(type: 'string', length: 16)]
    private $quotesMethod;

    #[ORM\OneToOne(targetEntity: Company::class, fetch: 'LAZY')]
    #[ORM\JoinColumn(name: 'vendorId', referencedColumnName: 'companyId', nullable: false)]
    private $vendor;

    public function getImportConnectionId(): ?int
    {
        return $this->importConnectionId;
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

    public function getEnabledProductCatalogImport(): ?bool
    {
        return $this->enabledProductCatalogImport;
    }

    public function setEnabledProductCatalogImport(?bool $enabledProductCatalogImport): self
    {
        $this->enabledProductCatalogImport = $enabledProductCatalogImport;

        return $this;
    }

    public function getEnabledQuoteImport(): ?bool
    {
        return $this->enabledQuoteImport;
    }

    public function setEnabledQuoteImport(?bool $enabledQuoteImport): self
    {
        $this->enabledQuoteImport = $enabledQuoteImport;

        return $this;
    }

    public function getQuotesMethod(): ?string
    {
        return $this->quotesMethod;
    }

    public function setQuotesMethod(string $quotesMethod): self
    {
        $this->quotesMethod = $quotesMethod;

        return $this;
    }

    public function getVendor(): ?Company
    {
        return $this->vendor;
    }

    public function setVendor(Company $vendor): self
    {
        $this->vendor = $vendor;

        return $this;
    }
}
