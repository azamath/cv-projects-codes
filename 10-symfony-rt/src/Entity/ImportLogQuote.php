<?php

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiFilter;
use ApiPlatform\Core\Annotation\ApiResource;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\DateFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\OrderFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\SearchFilter;
use App\Repository\ImportLogQuoteRepository;
use Doctrine\ORM\Mapping as ORM;

#[ApiResource(
    collectionOperations: ['get'],
    itemOperations: ['get'],
    attributes: ["security" => "is_granted('IS_AUTHENTICATED_FULLY')"],
)]
#[ORM\Entity(repositoryClass: ImportLogQuoteRepository::class)]
#[ORM\Table(name: 'import_log_quote')]
class ImportLogQuote implements \Stringable
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    #[ApiFilter(SearchFilter::class)]
    #[ApiFilter(OrderFilter::class)]
    private $importLogId;

    #[ORM\Column(type: 'integer')]
    #[ApiFilter(SearchFilter::class)]
    #[ApiFilter(OrderFilter::class)]
    private $userId;

    #[ORM\Column(type: 'integer')]
    #[ApiFilter(SearchFilter::class)]
    #[ApiFilter(OrderFilter::class)]
    private $vendorId;

    #[ORM\Column(type: 'string', length: 255)]
    #[ApiFilter(SearchFilter::class, strategy: SearchFilter::STRATEGY_PARTIAL)]
    private $fileName;

    #[ORM\Column(type: 'text', nullable: true)]
    private $validationResult;

    #[ORM\Column(type: 'integer')]
    #[ApiFilter(SearchFilter::class)]
    #[ApiFilter(OrderFilter::class)]
    private $importResult;

    #[ORM\Column(type: 'datetime')]
    #[ApiFilter(DateFilter::class)]
    #[ApiFilter(OrderFilter::class)]
    private $importDate;

    #[ORM\Column(type: 'integer', nullable: true)]
    #[ApiFilter(SearchFilter::class)]
    #[ApiFilter(OrderFilter::class)]
    private $resellerId;

    public function __toString(): string
    {
        $id = $this->getImportLogId() ?? 'NEW';
        return "ImportLogQuote.{$id}";
    }

    public function getImportLogId(): ?int
    {
        return $this->importLogId;
    }

    public function getUserId(): ?int
    {
        return $this->userId;
    }

    public function setUserId(int $userId): self
    {
        $this->userId = $userId;

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

    public function getFileName(): ?string
    {
        return $this->fileName;
    }

    public function setFileName(string $fileName): self
    {
        $this->fileName = $fileName;

        return $this;
    }

    public function getValidationResult(): ?string
    {
        return $this->validationResult;
    }

    public function setValidationResult(?string $validationResult): self
    {
        $this->validationResult = $validationResult;

        return $this;
    }

    public function getImportResult(): ?\App\Enum\EImportLogResult
    {
        return !is_null($this->importResult) ? \App\Enum\EImportLogResult::from($this->importResult) : null;
    }

    public function setImportResult(\App\Enum\EImportLogResult $importResult): self
    {
        $this->importResult = $importResult->value;

        return $this;
    }

    public function getImportDate(): ?\DateTimeInterface
    {
        return $this->importDate;
    }

    public function setImportDate(\DateTimeInterface $importDate): self
    {
        $this->importDate = $importDate;

        return $this;
    }

    public function getResellerId(): ?int
    {
        return $this->resellerId;
    }

    public function setResellerId(?int $resellerId): self
    {
        $this->resellerId = $resellerId;

        return $this;
    }
}
