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
    public const MANAGE = 'TASK_MANAGE'; // Nouvelle permission pour gérer le statut

    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, [self::VIEW, self::EDIT, self::DELETE, self::MANAGE])
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
            self::MANAGE => $this->canManage($task, $user),
            default => false,
        };
    }

    private function canView(Task $task, User $user): bool
    {
        // Un administrateur peut voir toutes les tâches
        if (in_array('ROLE_ADMIN', $user->getRoles())) {
            return true;
        }

        // L'utilisateur peut voir les tâches de ses propres projets
        if ($task->getProject()->getOwner() === $user) {
            return true;
        }

        // L'utilisateur peut voir les tâches qui lui sont assignées
        if ($task->getAssignee() === $user) {
            return true;
        }

        // L'utilisateur peut voir les tâches des projets où il collabore
        if ($task->getProject()->hasCollaborator($user)) {
            return true;
        }

        return false;
    }

    private function canEdit(Task $task, User $user): bool
    {
        // Un administrateur peut modifier toutes les tâches
        if (in_array('ROLE_ADMIN', $user->getRoles())) {
            return true;
        }

        // Le propriétaire du projet peut modifier toutes les tâches du projet
        if ($task->getProject()->getOwner() === $user) {
            return true;
        }

        // L'utilisateur assigné peut modifier certains aspects de sa tâche
        // (par exemple le statut, mais pas forcément le titre ou l'assignation)
        if ($task->getAssignee() === $user) {
            return true;
        }

        return false;
    }

    private function canDelete(Task $task, User $user): bool
    {
        // Un administrateur peut supprimer toutes les tâches
        if (in_array('ROLE_ADMIN', $user->getRoles())) {
            return true;
        }

        // Seul le propriétaire du projet peut supprimer les tâches
        // Les collaborateurs ne peuvent pas supprimer les tâches
        return $task->getProject()->getOwner() === $user;
    }

    /**
     * Nouvelle méthode : gestion du statut et actions limitées
     * Les collaborateurs peuvent gérer le statut de leurs tâches assignées
     */
    private function canManage(Task $task, User $user): bool
    {
        // Un administrateur peut gérer toutes les tâches
        if (in_array('ROLE_ADMIN', $user->getRoles())) {
            return true;
        }

        // Le propriétaire du projet peut gérer toutes les tâches
        if ($task->getProject()->getOwner() === $user) {
            return true;
        }

        // L'utilisateur assigné peut gérer sa propre tâche (changer le statut, etc.)
        if ($task->getAssignee() === $user) {
            return true;
        }

        return false;
    }
}