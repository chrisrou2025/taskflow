<?php

namespace App\Controller;

use App\Entity\Task;
use App\Entity\Project;
use App\Form\TaskType;
use App\Repository\TaskRepository;
use App\Repository\ProjectRepository;
use App\Service\NotificationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/tasks')]
#[IsGranted('ROLE_USER')]
class TaskController extends AbstractController
{
    public function __construct(
        private NotificationService $notificationService
    ) {}

    #[Route('/', name: 'task_index', methods: ['GET'])]
    public function index(
        Request $request,
        TaskRepository $taskRepository,
        ProjectRepository $projectRepository
    ): Response {
        $user = $this->getUser();
        $projectId = $request->query->get('project');
        $status = $request->query->get('status');
        $priority = $request->query->get('priority');

        $userProjects = $projectRepository->createQueryBuilder('p')
            ->leftJoin('p.tasks', 't')
            ->where('p.owner = :user OR t.assignee = :user')
            ->setParameter('user', $user)
            ->groupBy('p.id')
            ->getQuery()
            ->getResult();

        $project = null;
        $tasks = [];

        if ($projectId) {
            $project = $projectRepository->find($projectId);

            if ($project) {
                try {
                    $this->denyAccessUnlessGranted('PROJECT_VIEW', $project);
                    $tasks = $taskRepository->findByProjectWithFilters($project, $status, $priority);
                } catch (\Exception $e) {
                    $this->addFlash('error', 'Vous n\'avez pas accès à ce projet.');
                    return $this->redirectToRoute('task_index');
                }
            }
        } else {
            $qb = $taskRepository->createQueryBuilder('t')
                ->join('t.project', 'p')
                ->where('p.owner = :user OR t.assignee = :user')
                ->setParameter('user', $user)
                ->orderBy('t.createdAt', 'DESC')
                ->setMaxResults(50);

            $tasks = $qb->getQuery()->getResult();

            if ($status || $priority) {
                $tasks = array_filter($tasks, function ($task) use ($status, $priority) {
                    $statusMatch = !$status || $task->getStatus() === $status;
                    $priorityMatch = !$priority || $task->getPriority() === $priority;
                    return $statusMatch && $priorityMatch;
                });
            }
        }

        return $this->render('task/index.html.twig', [
            'tasks' => $tasks,
            'project' => $project,
            'projects' => $userProjects,
            'current_status' => $status,
            'current_priority' => $priority,
            'status_choices' => Task::getStatusChoices(),
            'priority_choices' => Task::getPriorityChoices(),
        ]);
    }

    #[Route('/new', name: 'task_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        EntityManagerInterface $entityManager,
        ProjectRepository $projectRepository
    ): Response {
        $task = new Task();
        $user = $this->getUser();
        $isCollaborator = false;

        $projectId = $request->query->get('project');
        if ($projectId) {
            $project = $projectRepository->find($projectId);

            if ($project) {
                try {
                    $this->denyAccessUnlessGranted('PROJECT_VIEW', $project);
                    $task->setProject($project);

                    // Vérifier si l'utilisateur est collaborateur (pas propriétaire)
                    $isCollaborator = $project->getOwner() !== $user;
                } catch (\Exception $e) {
                    $this->addFlash('error', 'Vous n\'avez pas accès à ce projet.');
                    return $this->redirectToRoute('project_index');
                }
            }
        }

        $form = $this->createForm(TaskType::class, $task, [
            'user' => $user,
            'is_collaborator' => $isCollaborator
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($task);
            $entityManager->flush();

            if ($task->getAssignee() && $task->getAssignee() !== $user) {
                try {
                    $this->notificationService->createTaskAssignedNotification($task, $user);
                } catch (\Exception $e) {
                    // Log l'erreur mais ne pas faire échouer la création de tâche
                }
            }

            $this->addFlash('success', 'La tâche "' . $task->getTitle() . '" a été créée avec succès !');

            if ($task->getProject()) {
                return $this->redirectToRoute('task_index', ['project' => $task->getProject()->getId()]);
            }
            return $this->redirectToRoute('task_index');
        }

        return $this->render('task/new.html.twig', [
            'task' => $task,
            'form' => $form,
        ]);
    }

    /**
     * Affiche les détails d'une tâche
     */
    #[Route('/{id}', name: 'task_show', methods: ['GET'])]
    public function show(Task $task): Response
    {
        $currentUser = $this->getUser();

        // Vérifier que l'utilisateur a accès à cette tâche
        if (!$this->isGranted('TASK_VIEW', $task)) {
            $this->addFlash('danger', 'Vous n\'êtes pas autorisé à voir cette tâche.');
            return $this->redirectToRoute('task_index');
        }

        // Déterminer si l'utilisateur est propriétaire du projet
        $isOwner = $task->getProject()->getOwner() === $currentUser;

        // Déterminer si l'utilisateur peut modifier (propriétaire OU assigné)
        $canEdit = $isOwner || $task->getAssignee() === $currentUser;

        return $this->render('task/show.html.twig', [
            'task' => $task,
            'isOwner' => $isOwner,
            'canEdit' => $canEdit,
        ]);
    }

    /**
     * Modifie une tâche
     */
    /**
     * Modifie une tâche
     */
    #[Route('/{id}/edit', name: 'task_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Task $task, EntityManagerInterface $entityManager): Response
    {
        $currentUser = $this->getUser();

        // Vérification personnalisée avant d'afficher le formulaire
        $isOwner = $task->getProject()->getOwner() === $currentUser;
        $isAssignee = $task->getAssignee() === $currentUser;

        if (!$isOwner && !$isAssignee) {
            $this->addFlash('danger', 'Action interdite : Vous n\'êtes pas autorisé à modifier cette tâche.');
            return $this->redirectToRoute('task_show', ['id' => $task->getId()]);
        }

        // Déterminer si l'utilisateur est un simple collaborateur (pas propriétaire)
        $isCollaborator = !$isOwner && $isAssignee;

        $form = $this->createForm(TaskType::class, $task, [
            'user' => $currentUser,
            'is_collaborator' => $isCollaborator
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $task->setUpdatedAt(new \DateTimeImmutable());
            $entityManager->flush();

            $this->addFlash('success', 'La tâche a été modifiée avec succès.');
            return $this->redirectToRoute('task_show', ['id' => $task->getId()]);
        }

        return $this->render('task/edit.html.twig', [
            'task' => $task,
            'form' => $form,
        ]);
    }
    /**
     * Change le statut d'une tâche (Toggle entre les statuts)
     */
    /**
     * Change le statut d'une tâche (Toggle entre les statuts)
     */
    #[Route('/{id}/toggle-status', name: 'task_toggle_status', methods: ['POST'])]
    public function toggleStatus(Request $request, Task $task, EntityManagerInterface $entityManager): Response
    {
        $currentUser = $this->getUser();

        // Vérification personnalisée
        $isOwner = $task->getProject()->getOwner() === $currentUser;
        $isAssignee = $task->getAssignee() === $currentUser;

        if (!$isOwner && !$isAssignee) {
            $this->addFlash('danger', 'Action interdite : Vous n\'êtes pas autorisé à actualiser cette tâche.');
            return $this->redirectToRoute('task_show', ['id' => $task->getId()]);
        }

        // Vérification du token CSRF
        if (!$this->isCsrfTokenValid('toggle-status' . $task->getId(), $request->request->get('_token'))) {
            $this->addFlash('danger', 'Token CSRF invalide.');
            return $this->redirectToRoute('task_show', ['id' => $task->getId()]);
        }

        // Cycle des statuts : todo -> in_progress -> completed -> todo
        $currentStatus = $task->getStatus();

        switch ($currentStatus) {
            case Task::STATUS_TODO:  // Utilisation de la constante
                $task->setStatus(Task::STATUS_IN_PROGRESS);
                $message = 'La tâche est maintenant en cours.';
                break;
            case Task::STATUS_IN_PROGRESS:
                $task->setStatus(Task::STATUS_COMPLETED);
                $message = 'La tâche a été marquée comme terminée.';
                break;
            case Task::STATUS_COMPLETED:
                $task->setStatus(Task::STATUS_TODO);  // Utilisation de la constante
                $message = 'La tâche a été remise à faire.';
                break;
            default:
                $task->setStatus(Task::STATUS_TODO);  // Utilisation de la constante
                $message = 'Le statut de la tâche a été mis à jour.';
        }

        $entityManager->flush();

        $this->addFlash('success', $message);
        return $this->redirectToRoute('task_show', ['id' => $task->getId()]);
    }

    /**
     * Supprime une tâche
     */
    #[Route('/{id}', name: 'task_delete', methods: ['POST'])]
    public function delete(Request $request, Task $task, EntityManagerInterface $entityManager): Response
    {
        // Vérification avec le voter
        if (!$this->isGranted('TASK_DELETE', $task)) {
            $this->addFlash('danger', 'Action interdite : Vous n\'êtes pas autorisé à supprimer cette tâche.');
            return $this->redirectToRoute('task_show', ['id' => $task->getId()]);
        }

        // Vérification du token CSRF
        if (!$this->isCsrfTokenValid('delete' . $task->getId(), $request->request->get('_token'))) {
            $this->addFlash('danger', 'Token CSRF invalide.');
            return $this->redirectToRoute('task_show', ['id' => $task->getId()]);
        }

        $projectId = $task->getProject()->getId();
        $taskTitle = $task->getTitle();

        $entityManager->remove($task);
        $entityManager->flush();

        $this->addFlash('success', sprintf('La tâche "%s" a été supprimée avec succès.', $taskTitle));
        return $this->redirectToRoute('project_show', ['id' => $projectId]);
    }

    #[Route('/project/{id}/quick-add', name: 'task_quick_add', methods: ['POST'])]
    public function quickAdd(Request $request, Project $project, EntityManagerInterface $entityManager): JsonResponse
    {
        try {
            $this->denyAccessUnlessGranted('PROJECT_EDIT', $project);
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Vous n\'avez pas l\'autorisation d\'ajouter une tâche à ce projet.'
            ], 403);
        }

        $title = $request->request->get('title');
        if (empty(trim($title))) {
            return new JsonResponse(['success' => false, 'message' => 'Le titre ne peut pas être vide'], 400);
        }

        if ($this->isCsrfTokenValid('quick-add', $request->request->get('_token'))) {
            $task = new Task();
            $task->setTitle(trim($title));
            $task->setProject($project);
            $task->setStatus(Task::STATUS_TODO);
            $task->setPriority(Task::PRIORITY_MEDIUM);

            $entityManager->persist($task);
            $entityManager->flush();

            return new JsonResponse([
                'success' => true,
                'task' => [
                    'id' => $task->getId(),
                    'title' => $task->getTitle(),
                    'status' => $task->getStatus(),
                    'status_label' => $task->getStatusLabel(),
                    'priority_label' => $task->getPriorityLabel()
                ],
                'message' => 'Tâche créée avec succès'
            ]);
        }

        return new JsonResponse(['success' => false, 'message' => 'Token CSRF invalide'], 400);
    }
}
