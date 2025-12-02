<?php

namespace App\Entity;

use App\Repository\TransitionLogRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TransitionLogRepository::class)]
class TransitionLog
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'logs')]
    #[ORM\JoinColumn(nullable: false)]
    private ?WorkflowSubject $subject = null;

    #[ORM\Column(length: 255)]
    private ?string $transitionName = null;

    #[ORM\Column(length: 255)]
    private ?string $fromPlace = null;

    #[ORM\Column(length: 255)]
    private ?string $toPlace = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSubject(): ?WorkflowSubject
    {
        return $this->subject;
    }

    public function setSubject(?WorkflowSubject $subject): static
    {
        $this->subject = $subject;

        return $this;
    }

    public function getTransitionName(): ?string
    {
        return $this->transitionName;
    }

    public function setTransitionName(string $transitionName): static
    {
        $this->transitionName = $transitionName;

        return $this;
    }

    public function getFromPlace(): ?string
    {
        return $this->fromPlace;
    }

    public function setFromPlace(string $fromPlace): static
    {
        $this->fromPlace = $fromPlace;

        return $this;
    }

    public function getToPlace(): ?string
    {
        return $this->toPlace;
    }

    public function setToPlace(string $toPlace): static
    {
        $this->toPlace = $toPlace;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }
}
