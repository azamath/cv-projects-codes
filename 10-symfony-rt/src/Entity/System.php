<?php

namespace App\Entity;

use App\Repository\SystemRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SystemRepository::class)]
#[ORM\Table(name: 'systems')]
class System implements \Stringable
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $systemId;

    #[ORM\Column(type: 'integer')]
    private $companyId;

    #[ORM\Column(type: 'string', length: 255)]
    private $host;

    #[ORM\Column(type: 'integer')]
    private $port;

    #[ORM\Column(type: 'string', length: 8192)]
    private $publicKey;

    public function __toString(): string
    {
        $id = $this->getSystemId() ?? 'NEW';
        return "System.{$id}";
    }

    public function getSystemId(): ?int
    {
        return $this->systemId;
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

    public function getHost(): ?string
    {
        return $this->host;
    }

    public function setHost(string $host): self
    {
        $this->host = $host;

        return $this;
    }

    public function getPort(): ?int
    {
        return $this->port;
    }

    public function setPort(int $port): self
    {
        $this->port = $port;

        return $this;
    }

    public function getPublicKey(): ?string
    {
        return $this->publicKey;
    }

    public function setPublicKey(string $publicKey): self
    {
        $this->publicKey = $publicKey;

        return $this;
    }
}
