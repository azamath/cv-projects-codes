<?php

namespace App\Entity;

use App\Repository\LicenseRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: LicenseRepository::class)]
#[ORM\Table(name: 'licenses')]
class License implements \Stringable
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $licenseId;

    #[ORM\Column(type: 'string', length: 255)]
    private $licenseKey;

    #[ORM\Column(type: 'datetime')]
    private $licenseBegin;

    #[ORM\Column(type: 'datetime')]
    private $licenseExpire;

    #[ORM\Column(type: 'integer')]
    private $customerId;

    #[ORM\Column(type: 'integer')]
    private $companyId;

    #[ORM\ManyToOne(targetEntity: Company::class)]
    #[ORM\JoinColumn(name: 'companyId', referencedColumnName: 'companyId')]
    private ?Company $company;

    public function __toString(): string
    {
        $id = $this->licenseId ?? 'NEW';
        return "License.{$id}";
    }

    public function getLicenseId(): ?int
    {
        return $this->licenseId;
    }

    public function getLicenseKey(): ?string
    {
        return $this->licenseKey;
    }

    public function setLicenseKey(string $licenseKey): self
    {
        $this->licenseKey = $licenseKey;

        return $this;
    }

    public function getLicenseBegin(): ?\DateTimeInterface
    {
        return $this->licenseBegin;
    }

    public function setLicenseBegin(\DateTimeInterface $licenseBegin): self
    {
        $this->licenseBegin = $licenseBegin;

        return $this;
    }

    public function getLicenseExpire(): ?\DateTimeInterface
    {
        return $this->licenseExpire;
    }

    public function setLicenseExpire(\DateTimeInterface $licenseExpire): self
    {
        $this->licenseExpire = $licenseExpire;

        return $this;
    }

    public function getCustomerId(): ?int
    {
        return $this->customerId;
    }

    public function setCustomerId(int $customerId): self
    {
        $this->customerId = $customerId;

        return $this;
    }

    public function getCompanyId(): ?int
    {
        return $this->companyId;
    }

    public function setCompanyId(int $companyId): self
    {
        $this->companyId = $companyId;

        return $this;
    }

    public function getCompany(): ?Company
    {
        return $this->company;
    }

    public function setCompany(?Company $company): License
    {
        $this->company = $company;
        return $this;
    }
}
