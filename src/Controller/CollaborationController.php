<?php

namespace App\Controller;

use App\Entity\CollaborationRequest;
use App\Entity\Project;
use App\Entity\User;
use App\Form\CollaborationRequestType;
use App\Repository\CollaborationRequestRepository;
use App\Repository\ProjectRepository;
use App\Repository\UserRepository;
use App\Service\NotificationService;
use App\Service\CollaborationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;

#[Route('/collaboration')]
#[IsGranted('ROLE_USER')]
class CollaborationController extends AbstractController
{
    public function __construct(
        private NotificationService $notificationService,
        private EntityManagerInterface $entityManager,
        private CollaborationService $collaborationService
    ) {}

    /**
     * Affiche toutes les demandes de collaboration pour l'utilisateur connecté
     */
    #[Route('/requests', name: 'collaboration_requests', methods: ['GET'])]
    public function requests(CollaborationRequestRepository $collaborationRequestRepository): Response
    {
        $currentUser = $this->getUser();

        if (!$currentUser instanceof User) {
            throw $this->createAccessDeniedException('Vous devez être connecté.');
        }

        // Demandes reçues
        $receivedRequests = $collaborationRequestRepository->findBy(
            ['invitedUser' => $currentUser],
            ['createdAt' => 'DESC']
        );

        // Demandes envoyées
        $sentRequests = $collaborationRequestRepository->findBy(
            ['sender' => $currentUser],
            ['createdAt' => 'DESC']
        );

        return $this->render('collaboration/requests.html.twig', [
            'received_requests' => $receivedRequests,
            'sent_requests' => $sentRequests,
        ]);
    }

    /**
     * Affiche la liste des utilisateurs pour inviter à collaborer sur un projet
     */
    #[Route('/invite/{id}', name: 'collaboration_invite', methods: ['GET', 'POST'])]
    public function invite(
        Request $request,
        Project $project,
        UserRepository $userRepository,
        CollaborationRequestRepository $collaborationRequestRepository
    ): Response {
        $currentUser = $this->getUser();

        if (!$currentUser instanceof User) {
            throw $this->createAccessDeniedException('Vous devez être connecté.');
        }

        // Vérifier que l'utilisateur est propriétaire du projet
        if ($project->getOwner() !== $currentUser) {
            $this->addFlash('danger', 'Vous ne pouvez pas inviter de collaborateurs sur ce projet.');
            return $this->redirectToRoute('project_show', ['id' => $project->getId()]);
        }

        // Récupérer tous les utilisateurs (sauf l'actuel et ceux qui ont déjà une demande en cours)
        $excludedIds = [$currentUser->getId()];

        $existingRequests = $collaborationRequestRepository->findBy(
            ['project' => $project, 'status' => CollaborationRequest::STATUS_PENDING]
        );

        foreach ($existingRequests as $req) {
            $excludedIds[] = $req->getInvitedUser()->getId();
        }

        // On exclut aussi les collaborateurs actuels du projet
        foreach ($project->getCollaborators() as $collaborator) {
            $excludedIds[] = $collaborator->getId();
        }

        $usersToInvite = $userRepository->createQueryBuilder('u')
            ->where('u.id NOT IN (:excludedIds)')
            ->setParameter('excludedIds', $excludedIds)
            ->orderBy('u.lastName', 'ASC')
            ->addOrderBy('u.firstName', 'ASC')
            ->getQuery()
            ->getResult();

        $collaborationRequest = new CollaborationRequest();
        $form = $this->createForm(CollaborationRequestType::class, $collaborationRequest, [
            'available_users' => $usersToInvite
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $invitedUser = $form->get('invitedUser')->getData();

            if (!$invitedUser instanceof User) {
                $this->addFlash('danger', 'Utilisateur introuvable.');
                return $this->redirectToRoute('collaboration_invite', ['id' => $project->getId()]);
            }

            try {
                $this->collaborationService->createCollaborationRequest(
                    $project,
                    $currentUser,
                    $invitedUser,
                    $collaborationRequest->getMessage()
                );

                $this->addFlash('success', 'Votre demande de collaboration a bien été envoyée.');
                return $this->redirectToRoute('project_show', ['id' => $project->getId()]);
            } catch (BadRequestException $e) {
                $this->addFlash('danger', $e->getMessage());
            } catch (\Exception $e) {
                $this->addFlash('danger', 'Une erreur est survenue lors de l\'envoi de la demande.');
            }
        }

        return $this->render('collaboration/invite.html.twig', [
            'project' => $project,
            'form' => $form,
            'users' => $usersToInvite
        ]);
    }

    /**
     * Accepte une demande de collaboration
     */
    #[Route('/accept/{id}', name: 'collaboration_request_accept', methods: ['POST'])]
    public function accept(Request $request, CollaborationRequest $collaborationRequest): Response
    {
        $currentUser = $this->getUser();

        if (!$currentUser instanceof User) {
            throw $this->createAccessDeniedException('Vous devez être connecté.');
        }

        // Vérification du token CSRF
        if (!$this->isCsrfTokenValid('accept-request-' . $collaborationRequest->getId(), $request->request->get('_token'))) {
            $this->addFlash('danger', 'Token CSRF invalide.');
            return $this->redirectToRoute('collaboration_requests');
        }

        try {
            $this->collaborationService->acceptCollaborationRequest(
                $collaborationRequest,
                $currentUser,
                'Demande acceptée.'
            );

            $this->addFlash('success', 'Vous êtes maintenant collaborateur sur ce projet !');
            return $this->redirectToRoute('project_show', ['id' => $collaborationRequest->getProject()->getId()]);
        } catch (BadRequestException $e) {
            $this->addFlash('danger', $e->getMessage());
        } catch (\Exception $e) {
            $this->addFlash('danger', 'Une erreur est survenue lors de l\'acceptation de la demande.');
        }

        return $this->redirectToRoute('collaboration_requests');
    }

    /**
     * Refuse une demande de collaboration
     */
    #[Route('/refuse/{id}', name: 'collaboration_request_refuse', methods: ['POST'])]
    public function refuse(Request $request, CollaborationRequest $collaborationRequest): Response
    {
        $currentUser = $this->getUser();

        if (!$currentUser instanceof User) {
            throw $this->createAccessDeniedException('Vous devez être connecté.');
        }

        // Vérification du token CSRF
        if (!$this->isCsrfTokenValid('refuse-request-' . $collaborationRequest->getId(), $request->request->get('_token'))) {
            $this->addFlash('danger', 'Token CSRF invalide.');
            return $this->redirectToRoute('collaboration_requests');
        }

        try {
            $response = $request->request->get('response', 'Demande refusée.');
            $this->collaborationService->refuseCollaborationRequest(
                $collaborationRequest,
                $currentUser,
                $response
            );

            $this->addFlash('success', 'La demande a bien été refusée.');
        } catch (BadRequestException $e) {
            $this->addFlash('danger', $e->getMessage());
        } catch (\Exception $e) {
            $this->addFlash('danger', 'Une erreur est survenue lors du refus de la demande.');
        }

        return $this->redirectToRoute('collaboration_requests');
    }

    /**
     * Annule une demande de collaboration par l'expéditeur
     */
    #[Route('/cancel/{id}', name: 'collaboration_request_cancel', methods: ['POST'])]
    public function cancel(Request $request, CollaborationRequest $collaborationRequest): Response
    {
        $currentUser = $this->getUser();

        if (!$currentUser instanceof User) {
            throw $this->createAccessDeniedException('Vous devez être connecté.');
        }

        // Vérification du token CSRF
        if (!$this->isCsrfTokenValid('cancel-request-' . $collaborationRequest->getId(), $request->request->get('_token'))) {
            $this->addFlash('danger', 'Token CSRF invalide.');
            return $this->redirectToRoute('collaboration_requests');
        }

        try {
            $this->collaborationService->cancelCollaborationRequest($collaborationRequest, $currentUser);
            $this->addFlash('success', 'La demande de collaboration a bien été annulée.');
        } catch (BadRequestException $e) {
            $this->addFlash('danger', $e->getMessage());
        } catch (\Exception $e) {
            $this->addFlash('danger', 'Une erreur est survenue lors de l\'annulation de la demande.');
        }

        return $this->redirectToRoute('collaboration_requests');
    }

    /**
     * Retire un collaborateur d'un projet
     */
    #[Route('/project/{projectId}/remove-collaborator/{userId}', name: 'collaboration_remove_collaborator', methods: ['POST'])]
    public function removeCollaborator(
        Request $request,
        int $projectId,
        int $userId,
        ProjectRepository $projectRepository,
        UserRepository $userRepository
    ): Response {
        $currentUser = $this->getUser();

        if (!$currentUser instanceof User) {
            throw $this->createAccessDeniedException('Vous devez être connecté.');
        }

        $project = $projectRepository->find($projectId);
        $collaborator = $userRepository->find($userId);

        if (!$project || !$collaborator) {
            $this->addFlash('danger', 'Projet ou utilisateur introuvable.');
            return $this->redirectToRoute('project_index');
        }

        // Vérification du token CSRF
        if (!$this->isCsrfTokenValid('remove-collaborator-' . $projectId . '-' . $userId, $request->request->get('_token'))) {
            $this->addFlash('danger', 'Token CSRF invalide.');
            return $this->redirectToRoute('project_show', ['id' => $projectId]);
        }

        try {
            $this->collaborationService->removeCollaborator($project, $collaborator, $currentUser);

            $this->addFlash('success', sprintf(
                '%s a été retiré du projet avec succès.',
                $collaborator->getFullName()
            ));
        } catch (BadRequestException $e) {
            $this->addFlash('danger', $e->getMessage());
        } catch (\Exception $e) {
            $this->addFlash('danger', 'Une erreur est survenue lors du retrait du collaborateur.');
        }

        return $this->redirectToRoute('project_show', ['id' => $projectId]);
    }

    /**
     * Supprime définitivement une demande de collaboration
     */
    #[Route('/request/{id}/delete', name: 'collaboration_request_delete', methods: ['POST'])]
    public function delete(Request $request, CollaborationRequest $collaborationRequest): Response
    {
        $currentUser = $this->getUser();

        if (!$currentUser instanceof User) {
            throw $this->createAccessDeniedException('Vous devez être connecté.');
        }

        // Vérifier que l'utilisateur est concerné par cette demande
        if (
            $collaborationRequest->getSender() !== $currentUser &&
            $collaborationRequest->getInvitedUser() !== $currentUser
        ) {
            $this->addFlash('danger', 'Vous ne pouvez pas supprimer cette demande.');
            return $this->redirectToRoute('collaboration_requests');
        }

        // Vérifier le token CSRF
        if (!$this->isCsrfTokenValid(
            'delete-request-' . $collaborationRequest->getId(),
            $request->request->get('_token')
        )) {
            $this->addFlash('error', 'Token CSRF invalide.');
            return $this->redirectToRoute('collaboration_requests');
        }

        $projectTitle = $collaborationRequest->getProject()->getTitle();

        // Supprimer la demande
        $this->entityManager->remove($collaborationRequest);
        $this->entityManager->flush();

        $this->addFlash('success', sprintf(
            'La demande de collaboration pour le projet "%s" a été supprimée définitivement.',
            $projectTitle
        ));

        return $this->redirectToRoute('collaboration_requests');
    }

    /**
     * Supprime toutes les demandes reçues traitées (acceptées/refusées)
     */
    #[Route('/bulk-clear-processed-received', name: 'collaboration_bulk_clear_processed_received', methods: ['POST'])]
    public function bulkClearProcessedReceived(
        Request $request,
        CollaborationRequestRepository $collaborationRequestRepository
    ): JsonResponse {
        $currentUser = $this->getUser();

        if (!$currentUser instanceof User) {
            return new JsonResponse(['success' => false, 'message' => 'Non authentifié'], 401);
        }

        if (!$this->isCsrfTokenValid('bulk-clear-processed-received', $request->request->get('_token'))) {
            return new JsonResponse(['success' => false, 'message' => 'Token CSRF invalide'], 400);
        }

        $processedRequests = $collaborationRequestRepository->createQueryBuilder('cr')
            ->where('cr.invitedUser = :user')
            ->andWhere('cr.status IN (:statuses)')
            ->setParameter('user', $currentUser)
            ->setParameter('statuses', [
                CollaborationRequest::STATUS_ACCEPTED,
                CollaborationRequest::STATUS_REFUSED
            ])
            ->getQuery()
            ->getResult();

        $clearedCount = 0;
        foreach ($processedRequests as $processedRequest) {
            $this->entityManager->remove($processedRequest);
            $clearedCount++;
        }

        $this->entityManager->flush();

        return new JsonResponse([
            'success' => true,
            'message' => sprintf(
                '%d demande%s traitée%s supprimée%s avec succès.',
                $clearedCount,
                $clearedCount > 1 ? 's' : '',
                $clearedCount > 1 ? 's' : '',
                $clearedCount > 1 ? 's' : ''
            ),
            'count' => $clearedCount
        ]);
    }

    /**
     * Supprime toutes les demandes envoyées traitées
     */
    #[Route('/bulk-clear-processed-sent', name: 'collaboration_bulk_clear_processed_sent', methods: ['POST'])]
    public function bulkClearProcessedSent(
        Request $request,
        CollaborationRequestRepository $collaborationRequestRepository
    ): JsonResponse {
        $currentUser = $this->getUser();

        if (!$currentUser instanceof User) {
            return new JsonResponse(['success' => false, 'message' => 'Non authentifié'], 401);
        }

        if (!$this->isCsrfTokenValid('bulk-clear-processed-sent', $request->request->get('_token'))) {
            return new JsonResponse(['success' => false, 'message' => 'Token CSRF invalide'], 400);
        }

        $processedRequests = $collaborationRequestRepository->createQueryBuilder('cr')
            ->where('cr.sender = :user')
            ->andWhere('cr.status IN (:statuses)')
            ->setParameter('user', $currentUser)
            ->setParameter('statuses', [
                CollaborationRequest::STATUS_ACCEPTED,
                CollaborationRequest::STATUS_REFUSED,
                CollaborationRequest::STATUS_CANCELLED
            ])
            ->getQuery()
            ->getResult();

        $clearedCount = 0;
        foreach ($processedRequests as $processedRequest) {
            $this->entityManager->remove($processedRequest);
            $clearedCount++;
        }

        $this->entityManager->flush();

        return new JsonResponse([
            'success' => true,
            'message' => sprintf(
                '%d demande%s traitée%s supprimée%s avec succès.',
                $clearedCount,
                $clearedCount > 1 ? 's' : '',
                $clearedCount > 1 ? 's' : '',
                $clearedCount > 1 ? 's' : ''
            ),
            'count' => $clearedCount
        ]);
    }

    /**
     * Recherche les utilisateurs par nom, prénom ou email
     */
    #[Route('/search-users', name: 'collaboration_search_users', methods: ['GET'])]
    public function searchUsers(Request $request, UserRepository $userRepository): JsonResponse
    {
        $currentUser = $this->getUser();

        if (!$currentUser instanceof User) {
            return new JsonResponse(['error' => 'Non authentifié'], 401);
        }

        $query = $request->query->get('q');
        $projectId = $request->query->get('projectId');

        if (!$query) {
            return new JsonResponse([]);
        }

        $excludedIds = [$currentUser->getId()];

        if ($projectId) {
            $project = $this->entityManager->getRepository(Project::class)->find($projectId);
            if ($project) {
                // Exclure les collaborateurs existants
                foreach ($project->getCollaborators() as $collaborator) {
                    $excludedIds[] = $collaborator->getId();
                }

                // Exclure les utilisateurs ayant déjà une demande de collaboration en attente
                $existingRequests = $this->entityManager->getRepository(CollaborationRequest::class)
                    ->findBy(['project' => $project, 'status' => CollaborationRequest::STATUS_PENDING]);

                foreach ($existingRequests as $req) {
                    $excludedIds[] = $req->getInvitedUser()->getId();
                }
            }
        }

        $users = $userRepository->createQueryBuilder('u')
            ->where('u.id NOT IN (:excludedIds)')
            ->andWhere('(u.firstName LIKE :query OR u.lastName LIKE :query OR u.email LIKE :query)')
            ->setParameter('excludedIds', $excludedIds)
            ->setParameter('query', '%' . $query . '%')
            ->setMaxResults(10)
            ->orderBy('u.lastName', 'ASC')
            ->addOrderBy('u.firstName', 'ASC')
            ->getQuery()
            ->getResult();

        $results = array_map(fn(User $user) => [
            'id' => $user->getId(),
            'name' => $user->getFullName(),
            'email' => $user->getEmail(),
        ], $users);

        return new JsonResponse($results);
    }
}