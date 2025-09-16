<?php

namespace App\Service;

use App\Entity\Notification;
use App\Entity\CollaborationRequest;
use App\Entity\User;
use App\Entity\Project;
use App\Entity\Task;
use App\Repository\NotificationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class NotificationService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UrlGeneratorInterface $urlGenerator,
        private NotificationRepository $notificationRepository
    ) {
    }

    /**
     * Crée une notification de demande de collaboration
     */
    public function createCollaborationRequestNotification(CollaborationRequest $collaborationRequest): Notification
    {
        $notification = new Notification();
        $notification->setType(Notification::TYPE_COLLABORATION_REQUEST);
        $notification->setTitle('Nouvelle demande de collaboration');
        $notification->setMessage(sprintf(
            '%s vous invite à collaborer sur le projet "%s"',
            $collaborationRequest->getSender()->getFullName(),
            $collaborationRequest->getProject()->getTitle()
        ));
        $notification->setRecipient($collaborationRequest->getInvitedUser());
        $notification->setSender($collaborationRequest->getSender());
        $notification->setProject($collaborationRequest->getProject());
        
        // URL vers les demandes de collaboration
        $notification->setActionUrl($this->urlGenerator->generate('collaboration_requests'));
        
        $notification->setData([
            'collaboration_request_id' => $collaborationRequest->getId(),
            'project_id' => $collaborationRequest->getProject()->getId()
        ]);

        $this->entityManager->persist($notification);
        $this->entityManager->flush();

        return $notification;
    }

    /**
     * Crée une notification pour informer que la collaboration a été acceptée
     */
    public function createCollaborationAcceptedNotification(CollaborationRequest $collaborationRequest): Notification
    {
        $notification = new Notification();
        $notification->setType(Notification::TYPE_COLLABORATION_ACCEPTED);
        $notification->setTitle('Collaboration acceptée');
        $notification->setMessage(sprintf(
            '%s a accepté votre invitation à collaborer sur le projet "%s"',
            $collaborationRequest->getInvitedUser()->getFullName(),
            $collaborationRequest->getProject()->getTitle()
        ));
        $notification->setRecipient($collaborationRequest->getSender());
        $notification->setSender($collaborationRequest->getInvitedUser());
        $notification->setProject($collaborationRequest->getProject());
        $notification->setActionUrl($this->urlGenerator->generate('project_show', [
            'id' => $collaborationRequest->getProject()->getId()
        ]));
        $notification->setData([
            'collaboration_request_id' => $collaborationRequest->getId(),
            'response' => $collaborationRequest->getResponse()
        ]);

        $this->entityManager->persist($notification);
        $this->entityManager->flush();

        return $notification;
    }

    /**
     * Crée une notification pour informer que la collaboration a été refusée
     */
    public function createCollaborationRefusedNotification(CollaborationRequest $collaborationRequest): Notification
    {
        $notification = new Notification();
        $notification->setType(Notification::TYPE_COLLABORATION_REFUSED);
        $notification->setTitle('Collaboration déclinée');
        $notification->setMessage(sprintf(
            '%s a décliné votre invitation à collaborer sur le projet "%s"',
            $collaborationRequest->getInvitedUser()->getFullName(),
            $collaborationRequest->getProject()->getTitle()
        ));
        $notification->setRecipient($collaborationRequest->getSender());
        $notification->setSender($collaborationRequest->getInvitedUser());
        $notification->setProject($collaborationRequest->getProject());
        $notification->setActionUrl($this->urlGenerator->generate('collaboration_requests'));
        $notification->setData([
            'collaboration_request_id' => $collaborationRequest->getId(),
            'response' => $collaborationRequest->getResponse()
        ]);

        $this->entityManager->persist($notification);
        $this->entityManager->flush();

        return $notification;
    }

    /**
     * Crée une notification pour l'assignation d'une tâche
     */
    public function createTaskAssignedNotification(Task $task, User $assigner): Notification
    {
        if (!$task->getAssignee()) {
            throw new \InvalidArgumentException('La tâche doit avoir un assigné pour créer cette notification');
        }

        // Ne pas créer de notification si l'assigneur et l'assigné sont la même personne
        if ($task->getAssignee() === $assigner) {
            throw new \InvalidArgumentException('Pas besoin de notification si l\'utilisateur s\'assigne sa propre tâche');
        }

        $notification = new Notification();
        $notification->setType(Notification::TYPE_TASK_ASSIGNED);
        $notification->setTitle('Nouvelle tâche assignée');
        $notification->setMessage(sprintf(
            '%s vous a assigné la tâche "%s" dans le projet "%s"',
            $assigner->getFullName(),
            $task->getTitle(),
            $task->getProject()->getTitle()
        ));
        $notification->setRecipient($task->getAssignee());
        $notification->setSender($assigner);
        $notification->setProject($task->getProject());
        $notification->setTask($task);
        $notification->setActionUrl($this->urlGenerator->generate('task_show', [
            'id' => $task->getId()
        ]));
        $notification->setData([
            'task_id' => $task->getId(),
            'priority' => $task->getPriority(),
            'due_date' => $task->getDueDate()?->format('Y-m-d')
        ]);

        $this->entityManager->persist($notification);
        $this->entityManager->flush();

        return $notification;
    }

    /**
     * Crée une notification pour une mise à jour de projet
     */
    public function createProjectUpdateNotification(Project $project, User $updater, string $updateType, array $collaborators = []): void
    {
        if (empty($collaborators)) {
            $collaborators = $project->getCollaborators()->toArray();
        }

        $createdNotifications = 0;
        foreach ($collaborators as $collaborator) {
            // Ne pas notifier l'utilisateur qui fait la mise à jour
            if ($collaborator === $updater) {
                continue;
            }

            $notification = new Notification();
            $notification->setType(Notification::TYPE_PROJECT_UPDATE);
            $notification->setTitle('Mise à jour de projet');
            $notification->setMessage(sprintf(
                '%s a effectué une mise à jour sur le projet "%s"',
                $updater->getFullName(),
                $project->getTitle()
            ));
            $notification->setRecipient($collaborator);
            $notification->setSender($updater);
            $notification->setProject($project);
            $notification->setActionUrl($this->urlGenerator->generate('project_show', [
                'id' => $project->getId()
            ]));
            $notification->setData(['update_type' => $updateType]);

            $this->entityManager->persist($notification);
            $createdNotifications++;
        }

        if ($createdNotifications > 0) {
            $this->entityManager->flush();
        }
    }

    /**
     * Marque une notification comme lue
     */
    public function markAsRead(Notification $notification): void
    {
        if (!$notification->isRead()) {
            $notification->markAsRead();
            $this->entityManager->flush();
        }
    }

    /**
     * Marque toutes les notifications d'un utilisateur comme lues
     */
    public function markAllAsReadForUser(User $user): int
    {
        return $this->notificationRepository->markAllAsReadForUser($user);
    }

    /**
     * Supprime une notification
     */
    public function deleteNotification(Notification $notification): void
    {
        $this->entityManager->remove($notification);
        $this->entityManager->flush();
    }

    /**
     * Supprime toutes les notifications lues d'un utilisateur
     */
    public function deleteReadNotificationsForUser(User $user): int
    {
        return $this->notificationRepository->deleteReadForUser($user);
    }

    /**
     * Compte les notifications non lues pour un utilisateur
     */
    public function countUnreadForUser(User $user): int
    {
        return $this->notificationRepository->countUnreadForUser($user);
    }
    
    /**
     * Récupère les notifications paginées pour un utilisateur
     */
    public function getNotificationsForUser(User $user, int $page = 1, int $limit = 10): array
    {
        $offset = max(0, ($page - 1) * $limit);
        
        return $this->notificationRepository->findBy(
            ['recipient' => $user],
            ['createdAt' => 'DESC'],
            $limit,
            $offset
        );
    }

    /**
     * Récupère les notifications récentes pour un utilisateur
     */
    public function getRecentNotificationsForUser(User $user, int $limit = 5): array
    {
        return $this->notificationRepository->findBy(
            ['recipient' => $user],
            ['createdAt' => 'DESC'],
            max(1, min(20, $limit)) // Limiter entre 1 et 20
        );
    }

    /**
     * Nettoie les anciennes notifications lues (plus de 30 jours)
     */
    public function cleanupOldReadNotifications(\DateTimeInterface $beforeDate = null): int
    {
        if (!$beforeDate) {
            $beforeDate = new \DateTime('-30 days');
        }
        
        return $this->notificationRepository->deleteOldReadNotifications($beforeDate);
    }
}