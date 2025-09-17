<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`user`')]
#[UniqueEntity(fields: ['email'], message: 'Cette adresse email est déjà utilisée.')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180, unique: true)]
    #[Assert\Email(message: 'Veuillez saisir une adresse email valide.')]
    #[Assert\NotBlank(message: 'L\'email ne peut pas être vide.')]
    private ?string $email = null;

    #[ORM\Column]
    private array $roles = [];

    #[ORM\Column]
    private ?string $password = null;

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank(message: 'Le nom ne peut pas être vide.')]
    #[Assert\Length(
        min: 2,
        max: 100,
        minMessage: 'Le nom doit contenir au moins {{ limit }} caractères.',
        maxMessage: 'Le nom ne peut pas dépasser {{ limit }} caractères.'
    )]
    private ?string $firstName = null;

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank(message: 'Le prénom ne peut pas être vide.')]
    #[Assert\Length(
        min: 2,
        max: 100,
        minMessage: 'Le prénom doit contenir au moins {{ limit }} caractères.',
        maxMessage: 'Le prénom ne peut pas dépasser {{ limit }} caractères.'
    )]
    private ?string $lastName = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    // Champs pour la réinitialisation de mot de passe
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $resetToken = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $resetTokenExpiresAt = null;

    #[ORM\Column(type: 'boolean')]
    private bool $isVerified = false;

    // Relation OneToMany avec Project (projets possédés)
    #[ORM\OneToMany(mappedBy: 'owner', targetEntity: Project::class, orphanRemoval: true)]
    private Collection $projects;
    
    // Relation ManyToMany avec Project (projets sur lesquels l'utilisateur collabore)
    #[ORM\ManyToMany(targetEntity: Project::class, mappedBy: 'collaborators')]
    private Collection $collaborations;

    // Relation OneToMany avec Task pour l'assignation
    #[ORM\OneToMany(mappedBy: 'assignee', targetEntity: Task::class)]
    private Collection $assignedTasks;

    public function __construct()
    {
        $this->projects = new ArrayCollection();
        $this->collaborations = new ArrayCollection();
        $this->assignedTasks = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;
        return $this;
    }

    /**
     * Identifiant unique pour l'authentification
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    public function getRoles(): array
    {
        $roles = $this->roles;
        // Garantit que chaque utilisateur a au moins ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    public function setRoles(array $roles): static
    {
        $this->roles = $roles;
        return $this;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;
        return $this;
    }

    public function eraseCredentials(): void
    {
        // Nettoie les données sensibles temporaires si nécessaire
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function setFirstName(string $firstName): static
    {
        $this->firstName = $firstName;
        return $this;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function setLastName(string $lastName): static
    {
        $this->lastName = $lastName;
        return $this;
    }
    
    public function getFullName(): string
    {
        return trim($this->firstName . ' ' . $this->lastName);
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

    public function isVerified(): bool
    {
        return $this->isVerified;
    }

    public function setIsVerified(bool $isVerified): static
    {
        $this->isVerified = $isVerified;
        return $this;
    }

    // Getters et setters pour les champs de reset password
    public function getResetToken(): ?string
    {
        return $this->resetToken;
    }

    public function setResetToken(?string $resetToken): static
    {
        $this->resetToken = $resetToken;
        return $this;
    }

    public function getResetTokenExpiresAt(): ?\DateTimeImmutable
    {
        return $this->resetTokenExpiresAt;
    }

    public function setResetTokenExpiresAt(?\DateTimeImmutable $resetTokenExpiresAt): static
    {
        $this->resetTokenExpiresAt = $resetTokenExpiresAt;
        return $this;
    }

    // Méthodes pour la gestion des projets
    /**
     * @return Collection<int, Project>
     */
    public function getProjects(): Collection
    {
        return $this->projects;
    }

    public function addProject(Project $project): static
    {
        if (!$this->projects->contains($project)) {
            $this->projects->add($project);
            $project->setOwner($this);
        }

        return $this;
    }

    public function removeProject(Project $project): static
    {
        if ($this->projects->removeElement($project)) {
            // Définit le côté propriétaire à null (sauf si déjà modifié)
            if ($project->getOwner() === $this) {
                $project->setOwner(null);
            }
        }

        return $this;
    }
    
    /**
     * @return Collection<int, Project>
     */
    public function getCollaborations(): Collection
    {
        return $this->collaborations;
    }

    public function addCollaboration(Project $collaboration): static
    {
        if (!$this->collaborations->contains($collaboration)) {
            $this->collaborations->add($collaboration);
            $collaboration->addCollaborator($this);
        }

        return $this;
    }

    public function removeCollaboration(Project $collaboration): static
    {
        if ($this->collaborations->removeElement($collaboration)) {
            $collaboration->removeCollaborator($this);
        }

        return $this;
    }

    // Méthodes pour la gestion des tâches assignées
    /**
     * @return Collection<int, Task>
     */
    public function getAssignedTasks(): Collection
    {
        return $this->assignedTasks;
    }

    public function addAssignedTask(Task $task): static
    {
        if (!$this->assignedTasks->contains($task)) {
            $this->assignedTasks->add($task);
            $task->setAssignee($this);
        }

        return $this;
    }

    public function removeAssignedTask(Task $task): static
    {
        if ($this->assignedTasks->removeElement($task)) {
            // Définit la relation à null si c'était l'utilisateur assigné
            if ($task->getAssignee() === $this) {
                $task->setAssignee(null);
            }
        }

        return $this;
    }

    /**
     * Retourne le nombre de tâches assignées à cet utilisateur
     */
    public function getAssignedTasksCount(): int
    {
        return $this->assignedTasks->count();
    }

    /**
     * Retourne les tâches assignées par statut
     */
    public function getAssignedTasksByStatus(string $status): Collection
    {
        return $this->assignedTasks->filter(
            function (Task $task) use ($status) {
                return $task->getStatus() === $status;
            }
        );
    }

    /**
     * Retourne le nombre de tâches terminées assignées à cet utilisateur
     */
    public function getCompletedAssignedTasksCount(): int
    {
        return $this->getAssignedTasksByStatus(Task::STATUS_COMPLETED)->count();
    }

    /**
     * Retourne le nombre de tâches en cours assignées à cet utilisateur
     */
    public function getInProgressAssignedTasksCount(): int
    {
        return $this->getAssignedTasksByStatus(Task::STATUS_IN_PROGRESS)->count();
    }

    /**
     * Retourne le nombre de tâches à faire assignées à cet utilisateur
     */
    public function getTodoAssignedTasksCount(): int
    {
        return $this->getAssignedTasksByStatus(Task::STATUS_TODO)->count();
    }

    /**
     * Retourne les tâches assignées dans un projet spécifique
     */
    public function getAssignedTasksInProject(Project $project): Collection
    {
        return $this->assignedTasks->filter(
            function (Task $task) use ($project) {
                return $task->getProject() === $project;
            }
        );
    }

    /**
     * Retourne le nombre de tâches assignées dans un projet spécifique
     */
    public function getAssignedTasksCountInProject(Project $project): int
    {
        return $this->getAssignedTasksInProject($project)->count();
    }

    /**
     * Retourne les projets où l'utilisateur est collaborateur (a des tâches assignées)
     */
    public function getCollaboratingProjects(): Collection
    {
        $projects = new ArrayCollection();
        
        foreach ($this->assignedTasks as $task) {
            $project = $task->getProject();
            if (!$projects->contains($project)) {
                $projects->add($project);
            }
        }
        
        return $projects;
    }

    /**
     * Retourne tous les projets auxquels l'utilisateur participe (propriétaire ou collaborateur)
     */
    public function getAllRelatedProjects(): Collection
    {
        $allProjects = new ArrayCollection();
        
        // Ajouter les projets possédés
        foreach ($this->projects as $project) {
            if (!$allProjects->contains($project)) {
                $allProjects->add($project);
            }
        }
        
        // Ajouter les projets où on collabore
        foreach ($this->getCollaboratingProjects() as $project) {
            if (!$allProjects->contains($project)) {
                $allProjects->add($project);
            }
        }
        
        return $allProjects;
    }

    /**
     * Vérifie si l'utilisateur a des tâches en retard
     */
    public function hasOverdueTasks(): bool
    {
        foreach ($this->assignedTasks as $task) {
            if ($task->isOverdue()) {
                return true;
            }
        }
        return false;
    }

    /**
     * Retourne les tâches en retard assignées à cet utilisateur
     */
    public function getOverdueTasks(): Collection
    {
        return $this->assignedTasks->filter(
            function (Task $task) {
                return $task->isOverdue();
            }
        );
    }

    /**
     * Retourne les tâches récentes assignées à l'utilisateur
     */
    public function getRecentAssignedTasks(int $limit = 5): array
    {
        $tasks = $this->assignedTasks->toArray();
        
        // Trier par date de création décroissante
        usort($tasks, function (Task $a, Task $b) {
            return $b->getCreatedAt() <=> $a->getCreatedAt();
        });
        
        return array_slice($tasks, 0, $limit);
    }

    /**
     * Calcule le pourcentage de tâches terminées par l'utilisateur
     */
    public function getTaskCompletionPercentage(): int
    {
        $totalTasks = $this->getAssignedTasksCount();
        if ($totalTasks === 0) {
            return 0;
        }

        $completedTasks = $this->getCompletedAssignedTasksCount();
        return round(($completedTasks / $totalTasks) * 100);
    }

    /**
     * Vérifie si l'utilisateur collabore sur un projet spécifique
     */
    public function collaboratesOnProject(Project $project): bool
    {
        return $this->getAssignedTasksCountInProject($project) > 0;
    }

    /**
     * Méthode utile pour l'affichage
     */
    public function __toString(): string
    {
        return $this->getFullName();
    }
}