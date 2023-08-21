<?php

namespace App\Entity;

use App\Enum\EMarginCalculation;
use App\Enum\EMarginType;
use App\Enum\EMarginValueType;
use App\Repository\MarginSelfRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: MarginSelfRepository::class)]
#[ORM\Table(name: 'margins_self')]
class MarginSelf implements MarginInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $marginId;

    #[ORM\Column(type: 'integer', nullable: true)]
    private $vendorId;

    #[ORM\Column(type: 'integer', nullable: true)]
    private $resellerId;

    #[ORM\Column(type: 'float')]
    private $marginValue;

    #[ORM\Column(type: 'integer')]
    private $marginValueType;

    #[ORM\Column(type: 'integer')]
    private $calculationType;

    #[ORM\Column(type: 'integer')]
    private $marginType;

    #[ORM\Column(type: 'integer', nullable: true)]
    private $originId;

    #[ORM\Column(type: 'integer', nullable: true)]
    private $marginRemoteId;

    public function getMarginId(): ?int
    {
        return $this->marginId;
    }

    public function getVendorId(): ?int
    {
        return $this->vendorId;
    }

    public function setVendorId(?int $vendorId): self
    {
        $this->vendorId = $vendorId;

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

    public function getMarginValue(): float
    {
        return $this->marginValue;
    }

    public function setMarginValue(float $marginValue): self
    {
        $this->marginValue = $marginValue;

        return $this;
    }

    public function getMarginValueType(): EMarginValueType
    {
        return EMarginValueType::from($this->marginValueType);
    }

    public function setMarginValueType(EMarginValueType $marginValueType): self
    {
        $this->marginValueType = $marginValueType->value;

        return $this;
    }

    public function getCalculationType(): EMarginCalculation
    {
        return EMarginCalculation::from($this->calculationType);
    }

    public function setCalculationType(EMarginCalculation $calculationType): self
    {
        $this->calculationType = $calculationType->value;

        return $this;
    }

    public function getMarginType(): EMarginType
    {
        return EMarginType::from($this->marginType);
    }

    public function setMarginType(EMarginType $marginType): self
    {
        $this->marginType = $marginType->value;

        return $this;
    }

    public function getOriginId(): ?int
    {
        return $this->originId;
    }

    public function setOriginId(?int $originId): self
    {
        $this->originId = $originId;

        return $this;
    }

    public function getMarginRemoteId(): ?int
    {
        return $this->marginRemoteId;
    }

    public function setMarginRemoteId(?int $marginRemoteId): self
    {
        $this->marginRemoteId = $marginRemoteId;

        return $this;
    }
}
