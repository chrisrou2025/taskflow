<?php

namespace App\Controller;

use App\Entity\Project;
use App\Entity\Task;
use App\Entity\CollaborationRequest;
use App\Form\ProjectType;
use App\Repository\ProjectRepository;
use App\Repository\TaskRepository;
use App\Repository\CollaborationRequestRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\HttpFoundation\JsonResponse;

#[Route('/projects')]
#[IsGranted('ROLE_USER')]
class ProjectController extends AbstractController
{
    #[Route('/', name: 'project_index', methods: ['GET'])]
    public function index(ProjectRepository $projectRepository): Response
    {
        $projects = $projectRepository->findProjectsByUserWithCollaborations($this->getUser());

        return $this->render('project/index.html.twig', [
            'projects' => $projects,
        ]);
    }

    #[Route('/new', name: 'project_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $project = new Project();
        $form = $this->createForm(ProjectType::class, $project);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $project->setOwner($this->getUser());
            $entityManager->persist($project);
            $entityManager->flush();

            $this->addFlash('success', 'Le projet "' . $project->getTitle() . '" a été créé avec succès !');

            return $this->redirectToRoute('project_index');
        }

        return $this->render('project/new.html.twig', [
            'project' => $project,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'project_show', methods: ['GET'])]
    public function show(
        Project $project,
        TaskRepository $taskRepository,
        CollaborationRequestRepository $collaborationRequestRepository
    ): Response {
        $this->denyAccessUnlessGranted('PROJECT_VIEW', $project);

        $currentUser = $this->getUser();
        $tasks = $taskRepository->findByProjectWithFilters($project);

        $pendingRequests = [];
        if ($project->getOwner() === $currentUser) {
            $pendingRequests = $collaborationRequestRepository->findBy([
                'project' => $project,
                'status' => CollaborationRequest::STATUS_PENDING
            ], ['createdAt' => 'DESC']);
        }

        return $this->render('project/show.html.twig', [
            'project' => $project,
            'tasks' => $tasks,
            'pending_requests' => $pendingRequests,
            'is_owner' => $project->getOwner() === $currentUser,
            'is_collaborator' => $project->hasCollaborator($currentUser),
        ]);
    }

    #[Route('/{id}/edit', name: 'project_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Project $project, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('PROJECT_EDIT', $project);

        $form = $this->createForm(ProjectType::class, $project);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $project->setUpdatedAt(new \DateTimeImmutable());
            $entityManager->flush();

            $this->addFlash('success', 'Le projet "' . $project->getTitle() . '" a été mis à jour avec succès !');

            return $this->redirectToRoute('project_index');
        }

        return $this->render('project/edit.html.twig', [
            'project' => $project,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'project_delete', methods: ['POST'])]
    public function delete(Request $request, Project $project, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('PROJECT_DELETE', $project);

        if ($this->isCsrfTokenValid('delete' . $project->getId(), $request->request->get('_token'))) {
            $title = $project->getTitle();
            $entityManager->remove($project);
            $entityManager->flush();
            $this->addFlash('success', "Le projet \"$title\" a été supprimé.");
        }

        return $this->redirectToRoute('project_index');
    }

    #[Route('/api/{id}/collaborators', name: 'api_project_collaborators', methods: ['GET'])]
    public function getCollaborators(Project $project): JsonResponse
    {
        $this->denyAccessUnlessGranted('PROJECT_VIEW', $project);

        $collaborators = [];

        $collaborators[] = [
            'id' => $project->getOwner()->getId(),
            'fullName' => $project->getOwner()->getFullName() . ' (Propriétaire)',
            'email' => $project->getOwner()->getEmail(),
            'role' => 'owner'
        ];

        foreach ($project->getCollaborators() as $collaborator) {
            $collaborators[] = [
                'id' => $collaborator->getId(),
                'fullName' => $collaborator->getFullName(),
                'email' => $collaborator->getEmail(),
                'role' => 'collaborator'
            ];
        }

        return new JsonResponse($collaborators);
    }
}
