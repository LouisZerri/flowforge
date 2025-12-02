<?php

namespace App\Entity;

use App\Repository\WorkflowRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: WorkflowRepository::class)]
class Workflow
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Le nom est obligatoire')]
    private ?string $name = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(length: 255)]
    private ?string $initialPlace = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    /**
     * @var Collection<int, Place>
     */
    #[ORM\OneToMany(targetEntity: Place::class, mappedBy: 'workflow', orphanRemoval: true)]
    private Collection $places;

    /**
     * @var Collection<int, Transition>
     */
    #[ORM\OneToMany(targetEntity: Transition::class, mappedBy: 'workflow', orphanRemoval: true)]
    private Collection $transitions;

    /**
     * @var Collection<int, WorkflowSubject>
     */
    #[ORM\OneToMany(targetEntity: WorkflowSubject::class, mappedBy: 'workflow')]
    private Collection $workflowSubjects;

    public function __construct()
    {
        $this->places = new ArrayCollection();
        $this->transitions = new ArrayCollection();
        $this->workflowSubjects = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getInitialPlace(): ?string
    {
        return $this->initialPlace;
    }

    public function setInitialPlace(string $initialPlace): static
    {
        $this->initialPlace = $initialPlace;

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

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    /**
     * @return Collection<int, Place>
     */
    public function getPlaces(): Collection
    {
        return $this->places;
    }

    public function addPlace(Place $place): static
    {
        if (!$this->places->contains($place)) {
            $this->places->add($place);
            $place->setWorkflow($this);
        }

        return $this;
    }

    public function removePlace(Place $place): static
    {
        if ($this->places->removeElement($place)) {
            // set the owning side to null (unless already changed)
            if ($place->getWorkflow() === $this) {
                $place->setWorkflow(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Transition>
     */
    public function getTransitions(): Collection
    {
        return $this->transitions;
    }

    public function addTransition(Transition $transition): static
    {
        if (!$this->transitions->contains($transition)) {
            $this->transitions->add($transition);
            $transition->setWorkflow($this);
        }

        return $this;
    }

    public function removeTransition(Transition $transition): static
    {
        if ($this->transitions->removeElement($transition)) {
            // set the owning side to null (unless already changed)
            if ($transition->getWorkflow() === $this) {
                $transition->setWorkflow(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, WorkflowSubject>
     */
    public function getWorkflowSubjects(): Collection
    {
        return $this->workflowSubjects;
    }

    public function addWorkflowSubject(WorkflowSubject $workflowSubject): static
    {
        if (!$this->workflowSubjects->contains($workflowSubject)) {
            $this->workflowSubjects->add($workflowSubject);
            $workflowSubject->setWorkflow($this);
        }

        return $this;
    }

    public function removeWorkflowSubject(WorkflowSubject $workflowSubject): static
    {
        if ($this->workflowSubjects->removeElement($workflowSubject)) {
            // set the owning side to null (unless already changed)
            if ($workflowSubject->getWorkflow() === $this) {
                $workflowSubject->setWorkflow(null);
            }
        }

        return $this;
    }
}
