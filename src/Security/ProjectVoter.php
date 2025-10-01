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

        if (!$user instanceof User) {
            $this->logger->debug('ProjectVoter: User not authenticated', [
                'attribute' => $attribute,
                'project_id' => $subject->getId()
            ]);
            return false;
        }

        // IMPORTANT: Administrateurs ont tous les droits
        if (in_array('ROLE_ADMIN', $user->getRoles())) {
            $this->logger->info('ProjectVoter: Admin access granted', [
                'attribute' => $attribute,
                'admin_email' => $user->getEmail(),
                'project_id' => $subject->getId()
            ]);
            return true;
        }

        $project = $subject;

        $result = match ($attribute) {
            self::VIEW => $this->canView($project, $user),
            self::EDIT => $this->canEdit($project, $user),
            self::DELETE => $this->canDelete($project, $user),
            default => false,
        };

        $this->logger->debug('ProjectVoter decision', [
            'attribute' => $attribute,
            'project_id' => $project->getId(),
            'project_title' => $project->getTitle(),
            'user_id' => $user->getId(),
            'user_email' => $user->getEmail(),
            'is_owner' => $project->getOwner() === $user,
            'decision' => $result ? 'GRANTED' : 'DENIED'
        ]);

        return $result;
    }

    private function canView(Project $project, User $user): bool
    {
        if ($project->getOwner() === $user) {
            return true;
        }

        if ($project->hasCollaborator($user)) {
            return true;
        }

        return false;
    }

    private function canEdit(Project $project, User $user): bool
    {
        return $project->getOwner() === $user;
    }

    private function canDelete(Project $project, User $user): bool
    {
        return $project->getOwner() === $user;
    }
}
