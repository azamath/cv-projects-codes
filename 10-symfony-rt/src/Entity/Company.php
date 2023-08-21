<?php

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiFilter;
use ApiPlatform\Core\Annotation\ApiResource;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\OrderFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\SearchFilter;
use App\Enum\ECompanyType;
use App\Repository\CompanyRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ApiResource(
    collectionOperations: [
        'get',

         // Retrieves the collection of Vendors available for importing quotes.
         // Uses \App\Api\DataProvider\VendorsDataProvider.
        'vendors_import_quote' => [
            'method' => 'GET',
            'path' => '/vendors/import_quote',
        ],
    ],
    itemOperations: [
        'get'
    ],
    attributes: ["security" => "is_granted('IS_AUTHENTICATED_FULLY')"],
)]
#[ORM\Entity(repositoryClass: CompanyRepository::class)]
#[ORM\Table(name: 'companies')]
class Company implements \Stringable
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    #[ApiFilter(OrderFilter::class)]
    private $companyId;

    #[ORM\Column(type: 'integer')]
    #[ApiFilter(OrderFilter::class)]
    private $remoteCompanyId;

    #[ORM\Column(type: 'string', length: 255)]
    #[Assert\Length(max: 255)]
    #[ApiFilter(SearchFilter::class, strategy: 'i' . SearchFilter::STRATEGY_PARTIAL)]
    private $name;

    #[ORM\Column(type: 'string', length: 64)]
    #[Assert\Length(max: 64)]
    #[ApiFilter(SearchFilter::class, strategy: 'i' . SearchFilter::STRATEGY_PARTIAL)]
    #[ApiFilter(OrderFilter::class)]
    private $alias;

    #[ORM\Column(type: 'integer')]
    #[ApiFilter(SearchFilter::class, strategy: SearchFilter::STRATEGY_EXACT)]
    #[ApiFilter(OrderFilter::class)]
    private $companyType;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    #[Assert\Length(max: 255)]
    private $email;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    #[Assert\Length(max: 255)]
    private $telephone;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    #[Assert\Length(max: 255)]
    private $fax;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    #[Assert\Length(max: 255)]
    private $web;

    #[ORM\Column(type: 'text', nullable: true)]
    private $logo;

    #[ORM\Column(type: 'string', length: 64, nullable: true)]
    #[Assert\Length(max: 64)]
    private $logoType;

    #[ORM\Column(type: 'text', nullable: true)]
    private $description;

    public function __toString(): string
    {
        $id = $this->getCompanyId() ?? 'NEW';
        return "Company.{$id}";
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

    public function getRemoteCompanyId(): ?int
    {
        return $this->remoteCompanyId;
    }

    public function setRemoteCompanyId(int $remoteCompanyId): self
    {
        $this->remoteCompanyId = $remoteCompanyId;

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

    public function getAlias(): ?string
    {
        return $this->alias;
    }

    public function setAlias(string $alias): self
    {
        $this->alias = $alias;

        return $this;
    }

    public function getCompanyType(): ?ECompanyType
    {
        return !is_null($this->companyType) ? ECompanyType::from($this->companyType) : null;
    }

    public function setCompanyType(ECompanyType $companyType): self
    {
        $this->companyType = $companyType->value;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): self
    {
        $this->email = $email;

        return $this;
    }

    public function getTelephone(): ?string
    {
        return $this->telephone;
    }

    public function setTelephone(?string $telephone): self
    {
        $this->telephone = $telephone;

        return $this;
    }

    public function getFax(): ?string
    {
        return $this->fax;
    }

    public function setFax(?string $fax): self
    {
        $this->fax = $fax;

        return $this;
    }

    public function getWeb(): ?string
    {
        return $this->web;
    }

    public function setWeb(?string $web): self
    {
        $this->web = $web;

        return $this;
    }

    public function getLogo(): ?string
    {
        return $this->logo;
    }

    public function setLogo(?string $logo): self
    {
        $this->logo = $logo;

        return $this;
    }

    public function getLogoType(): ?string
    {
        return $this->logoType;
    }

    public function setLogoType(?string $logoType): self
    {
        $this->logoType = $logoType;

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
}
