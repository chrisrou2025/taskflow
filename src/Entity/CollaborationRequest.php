<?php

namespace App\Entity;

use App\Repository\CollaborationRequestRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: CollaborationRequestRepository::class)]
#[ORM\HasLifecycleCallbacks]
#[ORM\UniqueConstraint(columns: ['project_id', 'invited_user_id'], name: 'unique_project_invitation')]
class CollaborationRequest
{
    // Statuts de la demande de collaboration
    public const STATUS_PENDING = 'pending';
    public const STATUS_ACCEPTED = 'accepted';
    public const STATUS_REFUSED = 'refused';
    public const STATUS_CANCELLED = 'cancelled';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 20)]
    private ?string $status = self::STATUS_PENDING;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Assert\Length(
        max: 1000,
        maxMessage: 'Le message ne peut pas dépasser {{ limit }} caractères.'
    )]
    private ?string $message = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Assert\Length(
        max: 1000,
        maxMessage: 'La réponse ne peut pas dépasser {{ limit }} caractères.'
    )]
    private ?string $response = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $respondedAt = null;

    // Projet concerné
    #[ORM\ManyToOne(targetEntity: Project::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Project $project = null;

    // Utilisateur qui envoie l'invitation (propriétaire du projet)
    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, name: 'sender_id')]
    private ?User $sender = null;

    // Utilisateur invité
    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, name: 'invited_user_id')]
    private ?User $invitedUser = null;

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

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;
        
        // Marquer la date de réponse si le statut change vers accepté ou refusé
        if (in_array($status, [self::STATUS_ACCEPTED, self::STATUS_REFUSED])) {
            $this->respondedAt = new \DateTimeImmutable();
        }
        
        return $this;
    }

    public function getMessage(): ?string
    {
        return $this->message;
    }

    public function setMessage(?string $message): static
    {
        $this->message = $message;
        return $this;
    }

    public function getResponse(): ?string
    {
        return $this->response;
    }

    public function setResponse(?string $response): static
    {
        $this->response = $response;
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

    public function getRespondedAt(): ?\DateTimeImmutable
    {
        return $this->respondedAt;
    }

    public function setRespondedAt(?\DateTimeImmutable $respondedAt): static
    {
        $this->respondedAt = $respondedAt;
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

    public function getSender(): ?User
    {
        return $this->sender;
    }

    public function setSender(?User $sender): static
    {
        $this->sender = $sender;
        return $this;
    }

    public function getInvitedUser(): ?User
    {
        return $this->invitedUser;
    }

    public function setInvitedUser(?User $invitedUser): static
    {
        $this->invitedUser = $invitedUser;
        return $this;
    }

    /**
     * Accepte la demande de collaboration
     */
    public function accept(?string $response = null): static
    {
        $this->setStatus(self::STATUS_ACCEPTED);
        $this->setResponse($response);
        return $this;
    }

    /**
     * Refuse la demande de collaboration
     */
    public function refuse(?string $response = null): static
    {
        $this->setStatus(self::STATUS_REFUSED);
        $this->setResponse($response);
        return $this;
    }

    /**
     * Annule la demande de collaboration
     */
    public function cancel(): static
    {
        $this->setStatus(self::STATUS_CANCELLED);
        return $this;
    }

    /**
     * Vérifie si la demande est en attente
     */
    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * Vérifie si la demande a été acceptée
     */
    public function isAccepted(): bool
    {
        return $this->status === self::STATUS_ACCEPTED;
    }

    /**
     * Vérifie si la demande a été refusée
     */
    public function isRefused(): bool
    {
        return $this->status === self::STATUS_REFUSED;
    }

    /**
     * Vérifie si la demande a été annulée
     */
    public function isCancelled(): bool
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    /**
     * Vérifie si une réponse a été donnée
     */
    public function hasBeenAnswered(): bool
    {
        return in_array($this->status, [self::STATUS_ACCEPTED, self::STATUS_REFUSED]);
    }

    /**
     * Retourne le libellé du statut en français
     */
    public function getStatusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING => 'En attente',
            self::STATUS_ACCEPTED => 'Acceptée',
            self::STATUS_REFUSED => 'Refusée',
            self::STATUS_CANCELLED => 'Annulée',
            default => 'Inconnu'
        };
    }

    /**
     * Retourne la classe CSS du badge selon le statut
     */
    public function getStatusBadgeClass(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING => 'bg-warning',
            self::STATUS_ACCEPTED => 'bg-success',
            self::STATUS_REFUSED => 'bg-danger',
            self::STATUS_CANCELLED => 'bg-secondary',
            default => 'bg-secondary'
        };
    }

    /**
     * Retourne l'icône CSS selon le statut
     */
    public function getStatusIcon(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING => 'fas fa-clock',
            self::STATUS_ACCEPTED => 'fas fa-check',
            self::STATUS_REFUSED => 'fas fa-times',
            self::STATUS_CANCELLED => 'fas fa-ban',
            default => 'fas fa-question'
        };
    }

    /**
     * Calcule le temps écoulé depuis la création
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
     * Vérifie si l'utilisateur peut répondre à cette demande
     */
    public function canBeAnsweredBy(User $user): bool
    {
        return $this->invitedUser === $user && $this->isPending();
    }

    /**
     * Vérifie si l'utilisateur peut annuler cette demande
     */
    public function canBeCancelledBy(User $user): bool
    {
        return $this->sender === $user && $this->isPending();
    }

    /**
     * Méthodes statiques utiles
     */
    public static function getStatusChoices(): array
    {
        return [
            'En attente' => self::STATUS_PENDING,
            'Acceptée' => self::STATUS_ACCEPTED,
            'Refusée' => self::STATUS_REFUSED,
            'Annulée' => self::STATUS_CANCELLED,
        ];
    }

    public function __toString(): string
    {
        return sprintf(
            'Demande de collaboration pour "%s" de %s vers %s',
            $this->project?->getTitle() ?? 'Projet inconnu',
            $this->sender?->getFullName() ?? 'Expéditeur inconnu',
            $this->invitedUser?->getFullName() ?? 'Destinataire inconnu'
        );
    }
}