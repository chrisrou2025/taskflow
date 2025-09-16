<?php

namespace App\Controller;

use App\Entity\CollaborationRequest;
use App\Entity\Notification;
use App\Entity\Project;
use App\Entity\User;
use App\Form\CollaborationRequestType;
use App\Repository\CollaborationRequestRepository;
use App\Repository\ProjectRepository;
use App\Repository\UserRepository;
use App\Repository\TaskRepository;
use App\Service\NotificationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/collaboration')]
#[IsGranted('ROLE_USER')]
class CollaborationController extends AbstractController
{
    public function __construct(
        private NotificationService $notificationService,
        private EntityManagerInterface $entityManager
    ) {
    }

    /**
     * Affiche toutes les demandes de collaboration pour l'utilisateur connecté
     */
    #[Route('/requests', name: 'collaboration_requests', methods: ['GET'])]
    public function requests(CollaborationRequestRepository $collaborationRequestRepository): Response
    {
        $currentUser = $this->getUser();

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
     * Affiche une demande de collaboration spécifique
     */
    #[Route('/request/{id}', name: 'collaboration_request_show', methods: ['GET'])]
    public function showRequest(CollaborationRequest $collaborationRequest): Response
    {
        $currentUser = $this->getUser();

        // Vérifier que l'utilisateur est concerné par cette demande
        if ($collaborationRequest->getSender() !== $currentUser && 
            $collaborationRequest->getInvitedUser() !== $currentUser) {
            $this->addFlash('danger', 'Accès non autorisé à cette demande.');
            return $this->redirectToRoute('collaboration_requests');
        }

        return $this->render('collaboration/show_request.html.twig', [
            'request' => $collaborationRequest,
            'perspective' => $collaborationRequest->getInvitedUser() === $currentUser ? 'recipient' : 'sender'
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

        // Vérifier que l'utilisateur est propriétaire du projet
        if (!$currentUser || $project->getOwner() !== $currentUser) {
            $this->addFlash('danger', 'Vous ne pouvez pas inviter de collaborateurs sur ce projet.');
            return $this->redirectToRoute('project_show', ['id' => $project->getId()]);
        }

        // Récupérer tous les utilisateurs (sauf l'actuel et ceux qui ont déjà une demande en cours)
        $excludedIds = [];
        if ($currentUser instanceof User) {
            $excludedIds[] = $currentUser->getId();
        }
        
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

            if ($invitedUser) {
                $collaborationRequest->setProject($project);
                $collaborationRequest->setSender($currentUser);
                $collaborationRequest->setInvitedUser($invitedUser);
                $collaborationRequest->setStatus(CollaborationRequest::STATUS_PENDING);

                $this->entityManager->persist($collaborationRequest);
                $this->entityManager->flush();

                // Envoyer la notification
                $this->notificationService->createCollaborationRequestNotification($collaborationRequest);

                $this->addFlash('success', 'Votre demande de collaboration a bien été envoyée.');
                return $this->redirectToRoute('project_show', ['id' => $project->getId()]);
            } else {
                $this->addFlash('danger', 'Utilisateur introuvable.');
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
    public function accept(CollaborationRequest $collaborationRequest): Response
    {
        $currentUser = $this->getUser();

        if (!$collaborationRequest->canBeAnsweredBy($currentUser)) {
            $this->addFlash('danger', 'Vous ne pouvez pas accepter cette demande.');
            return $this->redirectToRoute('collaboration_requests');
        }

        // Ajouter l'utilisateur au projet comme collaborateur
        $project = $collaborationRequest->getProject();
        $project->addCollaborator($currentUser);

        $collaborationRequest->accept('Demande acceptée.');

        $this->entityManager->flush();

        // Envoyer une notification à l'expéditeur
        $this->notificationService->createCollaborationAcceptedNotification($collaborationRequest);

        $this->addFlash('success', 'Vous êtes maintenant collaborateur sur ce projet !');
        return $this->redirectToRoute('project_show', ['id' => $project->getId()]);
    }

    /**
     * Refuse une demande de collaboration
     */
    #[Route('/refuse/{id}', name: 'collaboration_request_refuse', methods: ['POST'])]
    public function refuse(Request $request, CollaborationRequest $collaborationRequest): Response
    {
        $currentUser = $this->getUser();

        if (!$collaborationRequest->canBeAnsweredBy($currentUser)) {
            $this->addFlash('danger', 'Vous ne pouvez pas refuser cette demande.');
            return $this->redirectToRoute('collaboration_requests');
        }

        $response = $request->request->get('response', 'Demande refusée.');
        $collaborationRequest->refuse($response);

        $this->entityManager->flush();

        // Envoyer une notification à l'expéditeur
        $this->notificationService->createCollaborationRefusedNotification($collaborationRequest);

        $this->addFlash('success', 'La demande a bien été refusée.');
        return $this->redirectToRoute('collaboration_requests');
    }

    /**
     * Annule une demande de collaboration par l'expéditeur
     */
    #[Route('/cancel/{id}', name: 'collaboration_request_cancel', methods: ['POST'])]
    public function cancel(CollaborationRequest $collaborationRequest): Response
    {
        $currentUser = $this->getUser();

        if (!$collaborationRequest->canBeCancelledBy($currentUser)) {
            $this->addFlash('danger', 'Vous ne pouvez pas annuler cette demande.');
            return $this->redirectToRoute('collaboration_requests');
        }

        $collaborationRequest->cancel();
        $this->entityManager->flush();

        $this->addFlash('success', 'La demande de collaboration a bien été annulée.');
        return $this->redirectToRoute('collaboration_requests');
    }
    
    /**
     * Recherche les utilisateurs par nom, prénom ou email.
     */
    #[Route('/search-users', name: 'collaboration_search_users', methods: ['GET'])]
    public function searchUsers(Request $request, UserRepository $userRepository): JsonResponse
    {
        $query = $request->query->get('q');
        $projectId = $request->query->get('projectId');
        $currentUser = $this->getUser();

        if (!$query) {
            return new JsonResponse([]);
        }

        $excludedIds = [];
        if ($currentUser instanceof User) {
            $excludedIds[] = $currentUser->getId();
        }
    
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