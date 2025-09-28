<?php

namespace App\Security;

use App\Entity\Project;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Psr\Log\LoggerInterface;

class ProjectVoter extends Voter
{
    public const VIEW = 'PROJECT_VIEW';
    public const EDIT = 'PROJECT_EDIT';
    public const DELETE = 'PROJECT_DELETE';

    private LoggerInterface $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

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
            $this->logger->debug('ProjectVoter: User not authenticated', [
                'attribute' => $attribute,
                'project_id' => $subject->getId()
            ]);
            return false;
        }

        /** @var Project $project */
        $project = $subject;

        $result = match ($attribute) {
            self::VIEW => $this->canView($project, $user),
            self::EDIT => $this->canEdit($project, $user),
            self::DELETE => $this->canDelete($project, $user),
            default => false,
        };

        // Log pour débuggage des permissions
        $this->logger->debug('ProjectVoter decision', [
            'attribute' => $attribute,
            'project_id' => $project->getId(),
            'project_title' => $project->getTitle(),
            'user_id' => $user->getId(),
            'user_email' => $user->getEmail(),
            'user_roles' => $user->getRoles(),
            'is_owner' => $project->getOwner() === $user,
            'decision' => $result ? 'GRANTED' : 'DENIED'
        ]);

        return $result;
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
        // CORRECTION: Un administrateur peut supprimer tous les projets
        if (in_array('ROLE_ADMIN', $user->getRoles())) {
            $this->logger->info('Admin project deletion authorized', [
                'admin_user' => $user->getEmail(),
                'project_id' => $project->getId(),
                'project_owner' => $project->getOwner()->getEmail()
            ]);
            return true;
        }

        // Seul le propriétaire peut supprimer le projet
        // Les collaborateurs ne peuvent pas supprimer le projet
        $canDelete = $project->getOwner() === $user;
        
        if ($canDelete) {
            $this->logger->info('Owner project deletion authorized', [
                'owner_user' => $user->getEmail(),
                'project_id' => $project->getId()
            ]);
        }
        
        return $canDelete;
    }
}