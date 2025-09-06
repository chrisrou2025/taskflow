<?php

namespace App\Security;

use App\Entity\Task;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class TaskVoter extends Voter
{
    public const VIEW = 'TASK_VIEW';
    public const EDIT = 'TASK_EDIT';
    public const DELETE = 'TASK_DELETE';

    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, [self::VIEW, self::EDIT, self::DELETE])
            && $subject instanceof Task;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        // L'utilisateur doit être connecté
        if (!$user instanceof User) {
            return false;
        }

        /** @var Task $task */
        $task = $subject;

        return match ($attribute) {
            self::VIEW => $this->canView($task, $user),
            self::EDIT => $this->canEdit($task, $user),
            self::DELETE => $this->canDelete($task, $user),
            default => false,
        };
    }

    private function canView(Task $task, User $user): bool
    {
        // L'utilisateur peut voir les tâches de ses propres projets
        // Un administrateur peut voir toutes les tâches
        return $task->getProject()->getOwner() === $user || in_array('ROLE_ADMIN', $user->getRoles());
    }

    private function canEdit(Task $task, User $user): bool
    {
        // L'utilisateur peut modifier les tâches de ses propres projets
        // Un administrateur peut modifier toutes les tâches
        return $task->getProject()->getOwner() === $user || in_array('ROLE_ADMIN', $user->getRoles());
    }

    private function canDelete(Task $task, User $user): bool
    {
        // L'utilisateur peut supprimer les tâches de ses propres projets
        // Un administrateur peut supprimer toutes les tâches
        return $task->getProject()->getOwner() === $user || in_array('ROLE_ADMIN', $user->getRoles());
    }
}
