<?php

namespace App\Entity;

use App\Enum\ESigningState;
use App\Enum\EStateUpdateMethod;
use Doctrine\ORM\Mapping as ORM;

/**
 * SigningState
 */
#[ORM\Table(name: 'signings_state')]
#[ORM\Entity]
#[ORM\HasLifecycleCallbacks]
class SigningState
{
    use SetsModifiedDate;

    /**
     * @var int
     */
    #[ORM\Id]
    #[ORM\Column(name: 'signingStateId', type: 'integer', nullable: false)]
    #[ORM\GeneratedValue]
    private $signingStateId;

    /**
     * @var int
     */
    #[ORM\Column(name: 'signingId', type: 'integer', nullable: false)]
    private $signingId;

    /**
     * @var int
     */
    #[ORM\Column(name: 'state', type: 'integer', nullable: false)]
    private $state = 0;

    /**
     * @var \DateTime
     */
    #[ORM\Column(name: 'modifiedDate', type: 'datetime', nullable: false)]
    private $modifiedDate;

    /**
     * @var int
     */
    #[ORM\Column(name: 'modifiedUserId', type: 'integer', nullable: false)]
    private $modifiedUserId;

    #[ORM\Column(type: 'string', length: 16)]
    private $stateUpdateMethod = 'manual';

    #[ORM\OneToOne(targetEntity: Signing::class, inversedBy: 'signingState', cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(name: 'signingId', referencedColumnName: 'signingId', nullable: false)]
    private $signing;

    public function getSigningStateId(): ?int
    {
        return $this->signingStateId;
    }

    public function setSigningStateId(int $signingStateId): self
    {
        $this->signingStateId = $signingStateId;

        return $this;
    }

    public function getSigningId(): ?int
    {
        return $this->signingId;
    }

    public function setSigningId(int $signingId): self
    {
        $this->signingId = $signingId;

        return $this;
    }

    public function getState(): ESigningState
    {
        return ESigningState::from($this->state);
    }

    public function setState(ESigningState $state): self
    {
        $this->state = $state->value;

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

    public function getStateUpdateMethod(): EStateUpdateMethod
    {
        return EStateUpdateMethod::from($this->stateUpdateMethod);
    }

    public function setStateUpdateMethod(EStateUpdateMethod $stateUpdateMethod): self
    {
        $this->stateUpdateMethod = $stateUpdateMethod->value;

        return $this;
    }

    public function getSigning(): ?Signing
    {
        return $this->signing;
    }

    public function setSigning(Signing $signing): self
    {
        $this->signing = $signing;

        return $this;
    }
}
