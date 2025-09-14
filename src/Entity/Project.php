<?php

namespace App\Entity;

use App\Repository\ProjectRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ProjectRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Project
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Le titre du projet ne peut pas être vide.')]
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

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    // Relation ManyToOne avec User (propriétaire du projet)
    #[ORM\ManyToOne(inversedBy: 'projects')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $owner = null;

    // Relation OneToMany avec Task
    #[ORM\OneToMany(mappedBy: 'project', targetEntity: Task::class, orphanRemoval: true)]
    private Collection $tasks;

    public function __construct()
    {
        $this->tasks = new ArrayCollection();
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

    public function getOwner(): ?User
    {
        return $this->owner;
    }

    public function setOwner(?User $owner): static
    {
        $this->owner = $owner;
        return $this;
    }

    /**
     * @return Collection<int, Task>
     */
    public function getTasks(): Collection
    {
        return $this->tasks;
    }

    public function addTask(Task $task): static
    {
        if (!$this->tasks->contains($task)) {
            $this->tasks->add($task);
            $task->setProject($this);
        }

        return $this;
    }

    public function removeTask(Task $task): static
    {
        if ($this->tasks->removeElement($task)) {
            // Définit le côté propriétaire à null (sauf si déjà modifié)
            if ($task->getProject() === $this) {
                $task->setProject(null);
            }
        }

        return $this;
    }

    /**
     * Retourne le nombre total de tâches
     */
    public function getTasksCount(): int
    {
        return $this->tasks->count();
    }

    /**
     * Retourne les tâches par statut
     */
    public function getTasksByStatus(string $status): Collection
    {
        return $this->tasks->filter(
            function (Task $task) use ($status) {
                return $task->getStatus() === $status;
            }
        );
    }

    /**
     * Retourne le nombre de tâches terminées
     */
    public function getCompletedTasksCount(): int
    {
        return $this->getTasksByStatus(Task::STATUS_COMPLETED)->count();
    }

    /**
     * Retourne le nombre de tâches en cours
     */
    public function getInProgressTasksCount(): int
    {
        return $this->getTasksByStatus(Task::STATUS_IN_PROGRESS)->count();
    }

    /**
     * Retourne le nombre de tâches à faire
     */
    public function getTodoTasksCount(): int
    {
        return $this->getTasksByStatus(Task::STATUS_TODO)->count();
    }

    /**
     * Calcule le pourcentage de progression du projet
     */
    public function getProgressPercentage(): float
    {
        $totalTasks = $this->getTasksCount();

        if ($totalTasks === 0) {
            return 0.0;
        }

        return round(($this->getCompletedTasksCount() / $totalTasks) * 100, 1);
    }

    /**
     * Retourne tous les collaborateurs uniques du projet (users assignés aux tâches)
     */
    public function getCollaborators(): Collection
    {
        $collaborators = new ArrayCollection();
        
        foreach ($this->tasks as $task) {
            $assignee = $task->getAssignee();
            if ($assignee !== null && !$collaborators->contains($assignee)) {
                $collaborators->add($assignee);
            }
        }
        
        return $collaborators;
    }

    /**
     * Retourne le nombre de collaborateurs uniques
     */
    public function getCollaboratorsCount(): int
    {
        return $this->getCollaborators()->count();
    }

    /**
     * Vérifie si un utilisateur est collaborateur sur ce projet
     */
    public function hasCollaborator(User $user): bool
    {
        return $this->getCollaborators()->contains($user);
    }

    /**
     * Retourne les tâches assignées à un utilisateur spécifique dans ce projet
     */
    public function getTasksForCollaborator(User $collaborator): Collection
    {
        return $this->tasks->filter(
            function (Task $task) use ($collaborator) {
                return $task->getAssignee() === $collaborator;
            }
        );
    }

    /**
     * Retourne les tâches en retard du projet
     */
    public function getOverdueTasks(): Collection
    {
        return $this->tasks->filter(
            function (Task $task) {
                return $task->isOverdue();
            }
        );
    }

    /**
     * Vérifie si le projet a des tâches en retard
     */
    public function hasOverdueTasks(): bool
    {
        return $this->getOverdueTasks()->count() > 0;
    }

    /**
     * Retourne les tâches récentes (limitées à un nombre donné)
     */
    public function getRecentTasks(int $limit = 5): array
    {
        $tasks = $this->tasks->toArray();
        
        // Trier par date de création décroissante
        usort($tasks, function (Task $a, Task $b) {
            return $b->getCreatedAt() <=> $a->getCreatedAt();
        });
        
        return array_slice($tasks, 0, $limit);
    }

    /**
     * Vérifie si le projet est terminé (toutes les tâches sont terminées)
     */
    public function isCompleted(): bool
    {
        $totalTasks = $this->getTasksCount();
        
        if ($totalTasks === 0) {
            return false;
        }
        
        return $this->getCompletedTasksCount() === $totalTasks;
    }

    /**
     * Retourne le statut global du projet
     */
    public function getStatus(): string
    {
        if ($this->isCompleted()) {
            return 'completed';
        }
        
        if ($this->getInProgressTasksCount() > 0) {
            return 'in_progress';
        }
        
        if ($this->getTodoTasksCount() > 0) {
            return 'pending';
        }
        
        return 'empty';
    }

    /**
     * Retourne le libellé du statut en français
     */
    public function getStatusLabel(): string
    {
        return match ($this->getStatus()) {
            'completed' => 'Terminé',
            'in_progress' => 'En cours',
            'pending' => 'En attente',
            'empty' => 'Vide',
            default => 'Inconnu'
        };
    }

    /**
     * Retourne les statistiques complètes du projet
     */
    public function getProjectStatistics(): array
    {
        return [
            'total_tasks' => $this->getTasksCount(),
            'completed_tasks' => $this->getCompletedTasksCount(),
            'in_progress_tasks' => $this->getInProgressTasksCount(),
            'todo_tasks' => $this->getTodoTasksCount(),
            'overdue_tasks' => $this->getOverdueTasks()->count(),
            'collaborators_count' => $this->getCollaboratorsCount(),
            'progress_percentage' => $this->getProgressPercentage(),
            'status' => $this->getStatus(),
            'status_label' => $this->getStatusLabel(),
            'has_overdue_tasks' => $this->hasOverdueTasks(),
            'is_completed' => $this->isCompleted()
        ];
    }

    /**
     * Méthode utile pour l'affichage
     */
    public function __toString(): string
    {
        return $this->title ?? '';
    }
}