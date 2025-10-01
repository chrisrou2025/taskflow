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
    public const MANAGE = 'TASK_MANAGE';

    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, [self::VIEW, self::EDIT, self::DELETE, self::MANAGE])
            && $subject instanceof Task;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        if (!$user instanceof User) {
            return false;
        }

        // IMPORTANT: Administrateurs ont tous les droits
        if (in_array('ROLE_ADMIN', $user->getRoles())) {
            return true;
        }

        $task = $subject;

        return match ($attribute) {
            self::VIEW => $this->canView($task, $user),
            self::EDIT => $this->canEdit($task, $user),
            self::DELETE => $this->canDelete($task, $user),
            self::MANAGE => $this->canManage($task, $user),
            default => false,
        };
    }

    private function canView(Task $task, User $user): bool
    {
        if ($task->getProject()->getOwner() === $user) {
            return true;
        }

        if ($task->getAssignee() === $user) {
            return true;
        }

        if ($task->getProject()->hasCollaborator($user)) {
            return true;
        }

        return false;
    }

    private function canEdit(Task $task, User $user): bool
    {
        if ($task->getProject()->getOwner() === $user) {
            return true;
        }

        if ($task->getAssignee() === $user) {
            return true;
        }

        return false;
    }

    private function canDelete(Task $task, User $user): bool
    {
        return $task->getProject()->getOwner() === $user;
    }

    private function canManage(Task $task, User $user): bool
    {
        if ($task->getProject()->getOwner() === $user) {
            return true;
        }

        if ($task->getAssignee() === $user) {
            return true;
        }

        return false;
    }
}
