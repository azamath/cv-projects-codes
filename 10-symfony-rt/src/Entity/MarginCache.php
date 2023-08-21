<?php

namespace App\Entity;

use App\Enum\EMarginCalculation;
use App\Enum\EMarginValueType;
use App\Repository\MarginCacheRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: MarginCacheRepository::class)]
#[ORM\Table(name: 'margins_cache')]
class MarginCache
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $marginCacheId;

    #[ORM\Column(type: 'string', length: 255)]
    private $marginHash;

    #[ORM\Column(type: 'float')]
    private $marginValue;

    #[ORM\Column(type: 'integer')]
    private $marginValueType;

    #[ORM\Column(type: 'integer')]
    private $calculationType;

    public function getMarginCacheId(): ?int
    {
        return $this->marginCacheId;
    }

    public function getMarginHash(): ?string
    {
        return $this->marginHash;
    }

    public function setMarginHash(string $marginHash): self
    {
        $this->marginHash = $marginHash;

        return $this;
    }

    public function getMarginValue(): ?float
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
}
