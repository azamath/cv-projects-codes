<?php

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use App\Repository\LocaleRepository;
use Doctrine\ORM\Mapping as ORM;

#[ApiResource(
    collectionOperations: ['get'],
    itemOperations: ['get'],
    attributes: [],
)]
#[ORM\Entity(repositoryClass: LocaleRepository::class)]
#[ORM\Table(name: 'locale')]
class Locale
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $localeId = null;

    #[ORM\Column]
    private ?int $remoteLocaleId = null;

    #[ORM\Column(length: 16)]
    private ?string $code = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    public function getLocaleId(): ?int
    {
        return $this->localeId;
    }

    public function getRemoteLocaleId(): ?int
    {
        return $this->remoteLocaleId;
    }

    public function setRemoteLocaleId(int $remoteLocaleId): self
    {
        $this->remoteLocaleId = $remoteLocaleId;

        return $this;
    }

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(string $code): self
    {
        $this->code = $code;

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
}
