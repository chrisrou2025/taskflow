<?php

namespace App\Security;

use App\Entity\Task;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Psr\Log\LoggerInterface;

class TaskVoter extends Voter
{
    public const VIEW = 'TASK_VIEW';
    public const EDIT = 'TASK_EDIT';
    public const DELETE = 'TASK_DELETE';
    public const MANAGE = 'TASK_MANAGE';

    private LoggerInterface $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, [self::VIEW, self::EDIT, self::DELETE, self::MANAGE])
            && $subject instanceof Task;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        if (!$user instanceof User) {
            $this->logger->debug('TaskVoter: User not authenticated', [
                'attribute' => $attribute,
                'task_id' => $subject->getId()
            ]);
            return false;
        }

        // IMPORTANT: Administrateurs ont tous les droits
        if (in_array('ROLE_ADMIN', $user->getRoles())) {
            $this->logger->info('TaskVoter: Admin access granted', [
                'attribute' => $attribute,
                'admin_email' => $user->getEmail(),
                'task_id' => $subject->getId()
            ]);
            return true;
        }

        $task = $subject;

        $result = match ($attribute) {
            self::VIEW => $this->canView($task, $user),
            self::EDIT => $this->canEdit($task, $user),
            self::DELETE => $this->canDelete($task, $user),
            self::MANAGE => $this->canManage($task, $user),
            default => false,
        };

        $this->logger->debug('TaskVoter decision', [
            'attribute' => $attribute,
            'task_id' => $task->getId(),
            'task_title' => $task->getTitle(),
            'project_id' => $task->getProject()->getId(),
            'user_id' => $user->getId(),
            'user_email' => $user->getEmail(),
            'is_project_owner' => $task->getProject()->getOwner() === $user,
            'is_assignee' => $task->getAssignee() === $user,
            'decision' => $result ? 'GRANTED' : 'DENIED'
        ]);

        return $result;
    }

    private function canView(Task $task, User $user): bool
    {
        // Le propriétaire du projet peut voir toutes les tâches
        if ($task->getProject()->getOwner() === $user) {
            return true;
        }

        // L'assigné peut voir sa tâche
        if ($task->getAssignee() === $user) {
            return true;
        }

        // Les collaborateurs peuvent voir les tâches du projet
        if ($task->getProject()->hasCollaborator($user)) {
            return true;
        }

        return false;
    }

    private function canEdit(Task $task, User $user): bool
    {
        // Le propriétaire du projet peut modifier toutes les tâches
        if ($task->getProject()->getOwner() === $user) {
            return true;
        }

        // L'assigné peut modifier sa propre tâche
        if ($task->getAssignee() === $user) {
            return true;
        }

        return false;
    }

    private function canDelete(Task $task, User $user): bool
    {
        // Seul le propriétaire du projet peut supprimer les tâches
        return $task->getProject()->getOwner() === $user;
    }

    private function canManage(Task $task, User $user): bool
    {
        // Le propriétaire du projet peut gérer toutes les tâches
        if ($task->getProject()->getOwner() === $user) {
            return true;
        }

        // L'assigné peut gérer sa propre tâche (changer le statut, etc.)
        if ($task->getAssignee() === $user) {
            return true;
        }

        return false;
    }
}