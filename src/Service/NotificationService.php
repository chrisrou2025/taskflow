<?php

namespace App\Service;

use App\Entity\Notification;
use App\Entity\CollaborationRequest;
use App\Entity\User;
use App\Entity\Task;
use App\Entity\Project;
use Doctrine\ORM\EntityManagerInterface;

class NotificationService
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {}

    /**
     * Crée une notification pour une demande de collaboration
     */
    public function createCollaborationRequestNotification(
        CollaborationRequest $request
    ): Notification {
        $notification = new Notification();
        $notification->setType(Notification::TYPE_COLLABORATION_REQUEST)
            ->setRecipient($request->getInvitedUser())
            ->setSender($request->getSender())
            ->setProject($request->getProject())
            ->setTitle('Nouvelle demande de collaboration')
            ->setMessage(sprintf(
                '%s vous invite à collaborer sur le projet "%s"',
                $request->getSender()->getFullName(),
                $request->getProject()->getTitle()
            ))
            ->setActionUrl('/collaboration/requests')
            ->setData([
                'collaboration_request_id' => $request->getId(),
                'project_id' => $request->getProject()->getId()
            ]);

        $this->entityManager->persist($notification);
        $this->entityManager->flush();

        return $notification;
    }

    /**
     * Crée une notification pour une acceptation de collaboration
     */
    public function createCollaborationAcceptedNotification(
        CollaborationRequest $request
    ): Notification {
        $notification = new Notification();
        $notification->setType(Notification::TYPE_COLLABORATION_ACCEPTED)
            ->setRecipient($request->getSender())
            ->setSender($request->getInvitedUser())
            ->setProject($request->getProject())
            ->setTitle('Collaboration acceptée')
            ->setMessage(sprintf(
                '%s a accepté votre invitation à collaborer sur le projet "%s"',
                $request->getInvitedUser()->getFullName(),
                $request->getProject()->getTitle()
            ))
            ->setActionUrl('/projects/' . $request->getProject()->getId())
            ->setData([
                'collaboration_request_id' => $request->getId(),
                'response' => $request->getResponse()
            ]);

        $this->entityManager->persist($notification);
        $this->entityManager->flush();

        return $notification;
    }

    /**
     * Crée une notification pour un refus de collaboration
     */
    public function createCollaborationRefusedNotification(
        CollaborationRequest $request
    ): Notification {
        $notification = new Notification();
        $notification->setType(Notification::TYPE_COLLABORATION_REFUSED)
            ->setRecipient($request->getSender())
            ->setSender($request->getInvitedUser())
            ->setProject($request->getProject())
            ->setTitle('Collaboration refusée')
            ->setMessage(sprintf(
                '%s a refusé votre invitation à collaborer sur le projet "%s"',
                $request->getInvitedUser()->getFullName(),
                $request->getProject()->getTitle()
            ))
            ->setActionUrl('/projects/' . $request->getProject()->getId())
            ->setData([
                'collaboration_request_id' => $request->getId(),
                'response' => $request->getResponse()
            ]);

        $this->entityManager->persist($notification);
        $this->entityManager->flush();

        return $notification;
    }

    /**
     * Crée une notification pour l'assignation d'une tâche
     */
    public function createTaskAssignedNotification(
        Task $task,
        User $assigner
    ): Notification {
        $notification = new Notification();
        $notification->setType(Notification::TYPE_TASK_ASSIGNED)
            ->setRecipient($task->getAssignee())
            ->setSender($assigner)
            ->setProject($task->getProject())
            ->setTask($task)
            ->setTitle('Nouvelle tâche assignée')
            ->setMessage(sprintf(
                '%s vous a assigné la tâche "%s" dans le projet "%s"',
                $assigner->getFullName(),
                $task->getTitle(),
                $task->getProject()->getTitle()
            ))
            ->setActionUrl('/tasks/' . $task->getId())
            ->setData([
                'task_id' => $task->getId(),
                'priority' => $task->getPriority()
            ]);

        $this->entityManager->persist($notification);
        $this->entityManager->flush();

        return $notification;
    }

    /**
     * Crée une notification pour le retrait d'un collaborateur
     */
    public function createCollaboratorRemovedNotification(
        Project $project,
        User $removedUser,
        User $remover
    ): Notification {
        $notification = new Notification();
        $notification->setType(Notification::TYPE_PROJECT_UPDATE)
            ->setRecipient($removedUser)
            ->setSender($remover)
            ->setProject($project)
            ->setTitle('Retrait d\'un projet')
            ->setMessage(sprintf(
                '%s vous a retiré du projet "%s"',
                $remover->getFullName(),
                $project->getTitle()
            ))
            ->setActionUrl('/projects')
            ->setData([
                'project_id' => $project->getId(),
                'action' => 'collaborator_removed'
            ]);

        $this->entityManager->persist($notification);
        $this->entityManager->flush();

        return $notification;
    }

    /**
     * Marque une notification comme lue
     */
    public function markAsRead(Notification $notification): void
    {
        $notification->markAsRead();
        $this->entityManager->flush();
    }

    /**
     * Marque toutes les notifications d'un utilisateur comme lues
     */
    public function markAllAsReadForUser(User $user): int
    {
        $count = $this->entityManager
            ->getRepository(Notification::class)
            ->markAllAsReadForUser($user);

        return $count;
    }

    /**
     * Compte les notifications non lues d'un utilisateur
     */
    public function countUnreadForUser(User $user): int
    {
        return $this->entityManager
            ->getRepository(Notification::class)
            ->countUnreadForUser($user);
    }

    /**
     * Récupère les notifications paginées pour un utilisateur
     */
    public function getNotificationsForUser(User $user, int $page, int $limit): array
    {
        return $this->entityManager
            ->getRepository(Notification::class)
            ->createQueryBuilder('n')
            ->where('n.recipient = :user')
            ->setParameter('user', $user)
            ->orderBy('n.createdAt', 'DESC')
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère les notifications récentes pour un utilisateur
     */
    public function getRecentNotificationsForUser(User $user, int $limit = 5): array
    {
        return $this->entityManager
            ->getRepository(Notification::class)
            ->createQueryBuilder('n')
            ->where('n.recipient = :user')
            ->setParameter('user', $user)
            ->orderBy('n.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
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
        return $this->entityManager
            ->getRepository(Notification::class)
            ->deleteReadForUser($user);
    }
}