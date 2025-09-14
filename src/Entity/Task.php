<?php

namespace App\Entity;

use App\Repository\TaskRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: TaskRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Task
{
    // Constantes pour les statuts
    public const STATUS_TODO = 'todo';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_COMPLETED = 'completed';

    // Constantes pour les priorités
    public const PRIORITY_LOW = 'low';
    public const PRIORITY_MEDIUM = 'medium';
    public const PRIORITY_HIGH = 'high';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Le titre de la tâche ne peut pas être vide.')]
    #[Assert\Length(
        min: 3,
        max: 255,
        minMessage: 'Le titre doit contenir au moins {{ limit }} caractères.',
        maxMessage: 'Le titre ne peut pas dépasser {{ limit }} caractères.'
    )]
    private ?string $title = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Assert\Length(
        max: 2000,
        maxMessage: 'La description ne peut pas dépasser {{ limit }} caractères.'
    )]
    private ?string $description = null;

    #[ORM\Column(length: 20)]
    #[Assert\Choice(
        choices: [self::STATUS_TODO, self::STATUS_IN_PROGRESS, self::STATUS_COMPLETED],
        message: 'Statut invalide.'
    )]
    private ?string $status = self::STATUS_TODO;

    #[ORM\Column(length: 20)]
    #[Assert\Choice(
        choices: [self::PRIORITY_LOW, self::PRIORITY_MEDIUM, self::PRIORITY_HIGH],
        message: 'Priorité invalide.'
    )]
    private ?string $priority = self::PRIORITY_MEDIUM;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dueDate = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $completedAt = null;

    // Relation ManyToOne avec Project
    #[ORM\ManyToOne(targetEntity: Project::class, inversedBy: 'tasks')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Project $project = null;

    // Relation ManyToOne avec User pour l'assignation (AJOUT PRINCIPAL)
    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'assignedTasks')]
    #[ORM\JoinColumn(name: 'assignee_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?User $assignee = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    #[ORM\PreUpdate]
    public function setUpdatedAtValue(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;
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

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
{
        // Vérifier si le statut change réellement
        if ($this->status !== $status) {
            $this->status = $status;
            
            // Si la tâche est marquée comme terminée, enregistrer la date
            if ($status === self::STATUS_COMPLETED) {
                $this->completedAt = new \DateTimeImmutable();
            } else {
                // Si la tâche n'est plus terminée, réinitialiser la date de completion
                $this->completedAt = null;
            }
        }

        return $this;
    }

    public function getPriority(): ?string
    {
        return $this->priority;
    }

    public function setPriority(string $priority): static
    {
        $this->priority = $priority;
        return $this;
    }

    public function getDueDate(): ?\DateTimeInterface
    {
        return $this->dueDate;
    }

    public function setDueDate(?\DateTimeInterface $dueDate): static
    {
        $this->dueDate = $dueDate;
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

    public function getCompletedAt(): ?\DateTimeImmutable
    {
        return $this->completedAt;
    }

    public function setCompletedAt(?\DateTimeImmutable $completedAt): static
    {
        $this->completedAt = $completedAt;
        return $this;
    }

    public function getProject(): ?Project
    {
        return $this->project;
    }

    public function setProject(?Project $project): static
    {
        $this->project = $project;
        return $this;
    }

    // Méthodes pour la gestion de l'assignation (NOUVELLES MÉTHODES)
    public function getAssignee(): ?User
    {
        return $this->assignee;
    }

    public function setAssignee(?User $assignee): static
    {
        $this->assignee = $assignee;
        return $this;
    }

    /**
     * Méthodes utilitaires pour les statuts
     */
    public static function getStatusChoices(): array
    {
        return [
            'À faire' => self::STATUS_TODO,
            'En cours' => self::STATUS_IN_PROGRESS,
            'Terminé' => self::STATUS_COMPLETED,
        ];
    }

    public static function getPriorityChoices(): array
    {
        return [
            'Basse' => self::PRIORITY_LOW,
            'Moyenne' => self::PRIORITY_MEDIUM,
            'Haute' => self::PRIORITY_HIGH,
        ];
    }

    /**
     * Retourne le libellé du statut en français
     */
    public function getStatusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_TODO => 'À faire',
            self::STATUS_IN_PROGRESS => 'En cours',
            self::STATUS_COMPLETED => 'Terminé',
            default => 'Inconnu'
        };
    }

    /**
     * Retourne le libellé de la priorité en français
     */
    public function getPriorityLabel(): string
    {
        return match ($this->priority) {
            self::PRIORITY_LOW => 'Basse',
            self::PRIORITY_MEDIUM => 'Moyenne',
            self::PRIORITY_HIGH => 'Haute',
            default => 'Inconnue'
        };
    }

    /**
     * Vérifie si la tâche est en retard
     */
    public function isOverdue(): bool
    {
        if ($this->dueDate === null || $this->status === self::STATUS_COMPLETED) {
            return false;
        }

        return $this->dueDate < new \DateTime();
    }

    /**
     * Vérifie si la tâche est terminée
     */
    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    /**
     * Retourne une classe CSS en fonction de la priorité
     */
    public function getPriorityCssClass(): string
    {
        return match ($this->priority) {
            self::PRIORITY_LOW => 'priority-low',
            self::PRIORITY_MEDIUM => 'priority-medium',
            self::PRIORITY_HIGH => 'priority-high',
            default => ''
        };
    }

    /**
     * Retourne une classe CSS en fonction du statut
     */
    public function getStatusCssClass(): string
    {
        return match ($this->status) {
            self::STATUS_TODO => 'status-todo',
            self::STATUS_IN_PROGRESS => 'status-progress',
            self::STATUS_COMPLETED => 'status-completed',
            default => ''
        };
    }

    /**
     * Vérifie si la tâche a un responsable assigné
     */
    public function hasAssignee(): bool
    {
        return $this->assignee !== null;
    }

    /**
     * Retourne le nom du responsable ou "Non attribué"
     */
    public function getAssigneeDisplayName(): string
    {
        return $this->assignee ? $this->assignee->getFullName() : 'Non attribué';
    }

    /**
     * Méthode utile pour l'affichage
     */
    public function __toString(): string
    {
        return $this->title ?? '';
    }
}