<?php

namespace App\Entity;

use App\Repository\NotificationRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: NotificationRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Notification
{
    // Types de notifications
    public const TYPE_COLLABORATION_REQUEST = 'collaboration_request';
    public const TYPE_COLLABORATION_ACCEPTED = 'collaboration_accepted';
    public const TYPE_COLLABORATION_REFUSED = 'collaboration_refused';
    public const TYPE_TASK_ASSIGNED = 'task_assigned';
    public const TYPE_PROJECT_UPDATE = 'project_update';

    // Statuts des notifications
    public const STATUS_UNREAD = 'unread';
    public const STATUS_READ = 'read';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50)]
    private ?string $type = null;

    #[ORM\Column(length: 255)]
    private ?string $title = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $message = null;

    #[ORM\Column(length: 20)]
    private ?string $status = self::STATUS_UNREAD;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $readAt = null;

    // Utilisateur qui reçoit la notification
    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $recipient = null;

    // Utilisateur qui a déclenché la notification (optionnel)
    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?User $sender = null;

    // CORRECTION : Ajout de inversedBy pour la relation avec Project
    #[ORM\ManyToOne(targetEntity: Project::class, inversedBy: 'notifications')]
    #[ORM\JoinColumn(nullable: true)]
    private ?Project $project = null;

    // Tâche concernée (optionnel)
    #[ORM\ManyToOne(targetEntity: Task::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?Task $task = null;

    // URL d'action (optionnel)
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $actionUrl = null;

    // Données JSON additionnelles (optionnel)
    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $data = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): static
    {
        $this->type = $type;
        return $this;
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

    public function getMessage(): ?string
    {
        return $this->message;
    }

    public function setMessage(string $message): static
    {
        $this->message = $message;
        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;
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

    public function getReadAt(): ?\DateTimeImmutable
    {
        return $this->readAt;
    }

    public function setReadAt(?\DateTimeImmutable $readAt): static
    {
        $this->readAt = $readAt;
        return $this;
    }

    public function getRecipient(): ?User
    {
        return $this->recipient;
    }

    public function setRecipient(?User $recipient): static
    {
        $this->recipient = $recipient;
        return $this;
    }

    public function getSender(): ?User
    {
        return $this->sender;
    }

    public function setSender(?User $sender): static
    {
        $this->sender = $sender;
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

    public function getTask(): ?Task
    {
        return $this->task;
    }

    public function setTask(?Task $task): static
    {
        $this->task = $task;
        return $this;
    }

    public function getActionUrl(): ?string
    {
        return $this->actionUrl;
    }

    public function setActionUrl(?string $actionUrl): static
    {
        $this->actionUrl = $actionUrl;
        return $this;
    }

    public function getData(): ?array
    {
        return $this->data;
    }

    public function setData(?array $data): static
    {
        $this->data = $data;
        return $this;
    }

    /**
     * Marque la notification comme lue
     */
    public function markAsRead(): static
    {
        $this->status = self::STATUS_READ;
        $this->readAt = new \DateTimeImmutable();
        return $this;
    }

    /**
     * Vérifie si la notification est lue
     */
    public function isRead(): bool
    {
        return $this->status === self::STATUS_READ;
    }

    /**
     * Vérifie si la notification est non lue
     */
    public function isUnread(): bool
    {
        return $this->status === self::STATUS_UNREAD;
    }

    /**
     * Retourne l'icône CSS à afficher selon le type
     */
    public function getIcon(): string
    {
        return match ($this->type) {
            self::TYPE_COLLABORATION_REQUEST => 'fas fa-user-plus',
            self::TYPE_COLLABORATION_ACCEPTED => 'fas fa-check-circle',
            self::TYPE_COLLABORATION_REFUSED => 'fas fa-times-circle',
            self::TYPE_TASK_ASSIGNED => 'fas fa-tasks',
            self::TYPE_PROJECT_UPDATE => 'fas fa-folder',
            default => 'fas fa-bell'
        };
    }

    /**
     * Retourne la classe CSS de couleur selon le type
     */
    public function getColorClass(): string
    {
        return match ($this->type) {
            self::TYPE_COLLABORATION_REQUEST => 'text-primary',
            self::TYPE_COLLABORATION_ACCEPTED => 'text-success',
            self::TYPE_COLLABORATION_REFUSED => 'text-danger',
            self::TYPE_TASK_ASSIGNED => 'text-info',
            self::TYPE_PROJECT_UPDATE => 'text-warning',
            default => 'text-secondary'
        };
    }

    /**
     * Retourne un format de date lisible
     */
    public function getTimeAgo(): string
    {
        $now = new \DateTimeImmutable();
        $diff = $now->getTimestamp() - $this->createdAt->getTimestamp();

        if ($diff < 60) {
            return 'Il y a moins d\'une minute';
        } elseif ($diff < 3600) {
            $minutes = floor($diff / 60);
            return "Il y a {$minutes} minute" . ($minutes > 1 ? 's' : '');
        } elseif ($diff < 86400) {
            $hours = floor($diff / 3600);
            return "Il y a {$hours} heure" . ($hours > 1 ? 's' : '');
        } elseif ($diff < 2592000) {
            $days = floor($diff / 86400);
            return "Il y a {$days} jour" . ($days > 1 ? 's' : '');
        } else {
            return $this->createdAt->format('d/m/Y à H:i');
        }
    }

    /**
     * Méthodes statiques pour les types de notification
     */
    public static function getTypeChoices(): array
    {
        return [
            'Demande de collaboration' => self::TYPE_COLLABORATION_REQUEST,
            'Collaboration acceptée' => self::TYPE_COLLABORATION_ACCEPTED,
            'Collaboration refusée' => self::TYPE_COLLABORATION_REFUSED,
            'Tâche assignée' => self::TYPE_TASK_ASSIGNED,
            'Mise à jour de projet' => self::TYPE_PROJECT_UPDATE,
        ];
    }

    public static function getStatusChoices(): array
    {
        return [
            'Non lue' => self::STATUS_UNREAD,
            'Lue' => self::STATUS_READ,
        ];
    }

    public function __toString(): string
    {
        return $this->title ?? '';
    }
}