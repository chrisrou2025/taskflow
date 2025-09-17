<?php

namespace App\Security;

use App\Entity\Project;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class ProjectVoter extends Voter
{
    public const VIEW = 'PROJECT_VIEW';
    public const EDIT = 'PROJECT_EDIT';
    public const DELETE = 'PROJECT_DELETE';

    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, [self::VIEW, self::EDIT, self::DELETE])
            && $subject instanceof Project;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        // L'utilisateur doit être connecté
        if (!$user instanceof User) {
            return false;
        }

        /** @var Project $project */
        $project = $subject;

        return match ($attribute) {
            self::VIEW => $this->canView($project, $user),
            self::EDIT => $this->canEdit($project, $user),
            self::DELETE => $this->canDelete($project, $user),
            default => false,
        };
    }

    private function canView(Project $project, User $user): bool
    {
        // Un administrateur peut voir tous les projets
        if (in_array('ROLE_ADMIN', $user->getRoles())) {
            return true;
        }

        // L'utilisateur peut voir ses propres projets
        if ($project->getOwner() === $user) {
            return true;
        }

        // L'utilisateur peut voir les projets où il collabore (a des tâches assignées)
        if ($project->hasCollaborator($user)) {
            return true;
        }

        return false;
    }

    private function canEdit(Project $project, User $user): bool
    {
        // Un administrateur peut modifier tous les projets
        if (in_array('ROLE_ADMIN', $user->getRoles())) {
            return true;
        }

        // Seul le propriétaire peut modifier le projet
        // Les collaborateurs ne peuvent pas modifier les informations du projet
        return $project->getOwner() === $user;
    }

    private function canDelete(Project $project, User $user): bool
    {
        // Un administrateur peut supprimer tous les projets
        if (in_array('ROLE_ADMIN', $user->getRoles())) {
            return true;
        }

        // Seul le propriétaire peut supprimer le projet
        // Les collaborateurs ne peuvent pas supprimer le projet
        return $project->getOwner() === $user;
    }
}