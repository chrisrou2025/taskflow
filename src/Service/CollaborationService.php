<?php

namespace App\Service;

use App\Entity\CollaborationRequest;
use App\Entity\Project;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;

class CollaborationService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private NotificationService $notificationService
    ) {}

    /**
     * Crée une demande de collaboration
     */
    public function createCollaborationRequest(
        Project $project,
        User $sender,
        User $invitedUser,
        ?string $message = null
    ): CollaborationRequest {
        // Vérification : l'utilisateur ne peut pas s'inviter lui-même
        if ($sender === $invitedUser) {
            throw new BadRequestException(
                'Vous ne pouvez pas vous inviter vous-même.'
            );
        }

        // Vérification : seul le propriétaire peut inviter
        if ($project->getOwner() !== $sender) {
            throw new BadRequestException(
                'Seul le propriétaire peut inviter des collaborateurs.'
            );
        }

        // Vérification : l'utilisateur n'est pas déjà collaborateur
        if ($project->hasCollaborator($invitedUser)) {
            throw new BadRequestException(
                'Cet utilisateur est déjà collaborateur sur ce projet.'
            );
        }

        // Vérification : pas de demande en attente existante
        $existingRequest = $this->entityManager
            ->getRepository(CollaborationRequest::class)
            ->findOneBy([
                'project' => $project,
                'invitedUser' => $invitedUser,
                'status' => CollaborationRequest::STATUS_PENDING
            ]);

        if ($existingRequest) {
            throw new BadRequestException(
                'Une invitation est déjà en attente pour cet utilisateur.'
            );
        }

        // Création de la demande
        $request = new CollaborationRequest();
        $request->setProject($project)
            ->setSender($sender)
            ->setInvitedUser($invitedUser)
            ->setMessage($message)
            ->setStatus(CollaborationRequest::STATUS_PENDING);

        $this->entityManager->persist($request);
        $this->entityManager->flush();

        // Envoi d'une notification
        $this->notificationService->createCollaborationRequestNotification($request);

        return $request;
    }

    /**
     * Accepte une demande de collaboration
     */
    public function acceptCollaborationRequest(
        CollaborationRequest $request,
        User $user,
        ?string $response = null
    ): void {
        // Vérification : seul l'utilisateur invité peut accepter
        if ($request->getInvitedUser() !== $user) {
            throw new BadRequestException(
                'Vous ne pouvez pas accepter cette invitation.'
            );
        }

        // Vérification : la demande doit être en attente
        if (!$request->isPending()) {
            throw new BadRequestException(
                'Cette demande a déjà été traitée.'
            );
        }

        // Vérification : l'utilisateur n'est pas déjà collaborateur
        $project = $request->getProject();
        if ($project->hasCollaborator($user)) {
            throw new BadRequestException(
                'Vous êtes déjà collaborateur sur ce projet.'
            );
        }

        // Acceptation de la demande
        $request->accept($response);

        // Ajout du collaborateur au projet
        $project->addCollaborator($user);

        $this->entityManager->flush();

        // Notification au propriétaire
        $this->notificationService->createCollaborationAcceptedNotification($request);
    }

    /**
     * Refuse une demande de collaboration
     */
    public function refuseCollaborationRequest(
        CollaborationRequest $request,
        User $user,
        ?string $response = null
    ): void {
        // Vérification : seul l'utilisateur invité peut refuser
        if ($request->getInvitedUser() !== $user) {
            throw new BadRequestException(
                'Vous ne pouvez pas refuser cette invitation.'
            );
        }

        // Vérification : la demande doit être en attente
        if (!$request->isPending()) {
            throw new BadRequestException(
                'Cette demande a déjà été traitée.'
            );
        }

        // Refus de la demande
        $request->refuse($response);
        $this->entityManager->flush();

        // Notification à l'expéditeur
        $this->notificationService->createCollaborationRefusedNotification($request);
    }

    /**
     * Annule une demande de collaboration
     */
    public function cancelCollaborationRequest(
        CollaborationRequest $request,
        User $user
    ): void {
        // Vérification : seul l'expéditeur peut annuler
        if ($request->getSender() !== $user) {
            throw new BadRequestException(
                'Vous ne pouvez pas annuler cette invitation.'
            );
        }

        // Vérification : la demande doit être en attente
        if (!$request->isPending()) {
            throw new BadRequestException(
                'Cette demande a déjà été traitée.'
            );
        }

        // Annulation de la demande
        $request->cancel();
        $this->entityManager->flush();
    }

    /**
     * Retire un collaborateur d'un projet
     */
    public function removeCollaborator(
        Project $project,
        User $collaborator,
        User $remover
    ): void {
        // Vérification : le collaborateur doit faire partie du projet
        if (!$project->hasCollaborator($collaborator)) {
            throw new BadRequestException(
                'Cet utilisateur n\'est pas collaborateur sur ce projet.'
            );
        }

        // Vérification : le propriétaire ne peut pas être retiré
        if ($project->getOwner() === $collaborator) {
            throw new BadRequestException(
                'Le propriétaire ne peut pas être retiré du projet.'
            );
        }

        // Vérification des permissions
        $isOwner = $project->getOwner() === $remover;
        $isSelfRemoval = $collaborator === $remover;

        if (!$isOwner && !$isSelfRemoval) {
            throw new BadRequestException(
                'Vous n\'avez pas les permissions pour retirer ce collaborateur.'
            );
        }

        // Retrait du projet
        $project->removeCollaborator($collaborator);

        // Désassignation des tâches assignées au collaborateur retiré
        foreach ($project->getTasksForCollaborator($collaborator) as $task) {
            $task->setAssignee(null);
        }

        $this->entityManager->flush();

        // Envoi de la notification seulement si c'est le propriétaire qui retire
        // (pas si le collaborateur se retire lui-même)
        if (!$isSelfRemoval) {
            $this->notificationService->createCollaboratorRemovedNotification(
                $project,
                $collaborator,
                $remover
            );
        }
    }

    /**
     * Vérifie si un utilisateur peut inviter des collaborateurs sur un projet
     */
    public function canInviteCollaborators(Project $project, User $user): bool
    {
        return $project->getOwner() === $user;
    }

    /**
     * Vérifie si un utilisateur peut répondre à une demande de collaboration
     */
    public function canRespondToRequest(CollaborationRequest $request, User $user): bool
    {
        return $request->getInvitedUser() === $user && $request->isPending();
    }

    /**
     * Vérifie si un utilisateur peut annuler une demande de collaboration
     */
    public function canCancelRequest(CollaborationRequest $request, User $user): bool
    {
        return $request->getSender() === $user && $request->isPending();
    }

    /**
     * Vérifie si un utilisateur peut retirer un collaborateur
     */
    public function canRemoveCollaborator(Project $project, User $collaborator, User $remover): bool
    {
        // Le propriétaire peut retirer n'importe quel collaborateur
        if ($project->getOwner() === $remover) {
            return true;
        }

        // Un collaborateur peut se retirer lui-même
        if ($collaborator === $remover && $project->hasCollaborator($collaborator)) {
            return true;
        }

        return false;
    }
}