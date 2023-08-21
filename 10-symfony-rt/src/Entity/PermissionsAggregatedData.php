<?php

namespace App\Entity;

use App\Repository\PermissionsAggregatedDataRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PermissionsAggregatedDataRepository::class)]
#[ORM\Table(name: 'permissions_aggregated_data')]
class PermissionsAggregatedData
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $aggregatedDataId;

    #[ORM\Column(type: 'integer', nullable: true)]
    private $userId;

    #[ORM\Column(type: 'integer', nullable: true)]
    private $userRoleId;

    #[ORM\Column(type: 'integer', nullable: true)]
    private $rolePermissionId;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private $permissionCode;

    #[ORM\Column(type: 'integer', nullable: true)]
    private $permissionId;

    #[ORM\Column(type: 'integer', nullable: true)]
    private $vendorId;

    public function getAggregatedDataId(): ?int
    {
        return $this->aggregatedDataId;
    }

    public function getUserId(): ?int
    {
        return $this->userId;
    }

    public function setUserId(?int $userId): self
    {
        $this->userId = $userId;

        return $this;
    }

    public function getUserRoleId(): ?int
    {
        return $this->userRoleId;
    }

    public function setUserRoleId(?int $userRoleId): self
    {
        $this->userRoleId = $userRoleId;

        return $this;
    }

    public function getRolePermissionId(): ?int
    {
        return $this->rolePermissionId;
    }

    public function setRolePermissionId(?int $rolePermissionId): self
    {
        $this->rolePermissionId = $rolePermissionId;

        return $this;
    }

    public function getPermissionCode(): ?string
    {
        return $this->permissionCode;
    }

    public function setPermissionCode(?string $permissionCode): self
    {
        $this->permissionCode = $permissionCode;

        return $this;
    }

    public function getPermissionId(): ?int
    {
        return $this->permissionId;
    }

    public function setPermissionId(?int $permissionId): self
    {
        $this->permissionId = $permissionId;

        return $this;
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
}
