<?php

namespace App\Entity;

use App\Repository\CurrencyRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CurrencyRepository::class)]
#[ORM\Table(name: 'currencies')]
#[ORM\HasLifecycleCallbacks]
class Currency
{
    use SetsCreatedDate;
    use SetsModifiedDate;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $currencyId;

    #[ORM\Column(type: 'datetime')]
    private $createdDate;

    #[ORM\Column(type: 'integer')]
    private $createdUserId;

    #[ORM\Column(type: 'datetime')]
    private $modifiedDate;

    #[ORM\Column(type: 'integer')]
    private $modifiedUserId;

    #[ORM\Column(type: 'float')]
    private $conversionRate;

    #[ORM\Column(type: 'string', length: 8)]
    private $currencySymbol;

    #[ORM\Column(type: 'string', length: 16)]
    private $currencyCode;

    public function getCurrencyId(): ?int
    {
        return $this->currencyId;
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

    public function getModifiedDate(): ?\DateTimeInterface
    {
        return $this->modifiedDate;
    }

    public function setModifiedDate(\DateTimeInterface $modifiedDate): self
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

    public function getConversionRate(): ?float
    {
        return $this->conversionRate;
    }

    public function setConversionRate(float $conversionRate): self
    {
        $this->conversionRate = $conversionRate;

        return $this;
    }

    public function getCurrencySymbol(): ?string
    {
        return $this->currencySymbol;
    }

    public function setCurrencySymbol(string $currencySymbol): self
    {
        $this->currencySymbol = $currencySymbol;

        return $this;
    }

    public function getCurrencyCode(): ?string
    {
        return $this->currencyCode;
    }

    public function setCurrencyCode(string $currencyCode): self
    {
        $this->currencyCode = $currencyCode;

        return $this;
    }
}
