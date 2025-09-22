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
    ) {
    }

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

        // récupérer les projets dont l'utilisateur est owner OU collaborateur (assignee)
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

            // accès si propriétaire ou collaborateur
            if ($project && ($project->getOwner() === $user || $project->hasCollaborator($user))) {
                $tasks = $taskRepository->findByProjectWithFilters($project, $status, $priority);
            }
        } else {
            // toutes les tâches visibles (owner ou assignee)
            $qb = $taskRepository->createQueryBuilder('t')
                ->join('t.project', 'p')
                ->where('p.owner = :user OR t.assignee = :user')
                ->setParameter('user', $user)
                ->orderBy('t.createdAt', 'DESC')
                ->setMaxResults(50);

            $tasks = $qb->getQuery()->getResult();

            // Appliquer les filtres si nécessaires
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

        // Projet précisé dans l'URL
        $projectId = $request->query->get('project');
        if ($projectId) {
            $project = $projectRepository->find($projectId);

            // autorisé si propriétaire ou collaborateur
            if ($project && ($project->getOwner() === $user || $project->hasCollaborator($user))) {
                $task->setProject($project);
            }
        }

        $form = $this->createForm(TaskType::class, $task, [
            'user' => $user
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($task);
            $entityManager->flush();

            // Envoyer une notification si une personne est assignée et ce n'est pas le créateur
            if ($task->getAssignee() && $task->getAssignee() !== $user) {
                try {
                    $this->notificationService->createTaskAssignedNotification($task, $user);
                } catch (\Exception $e) {
                    // Log l'erreur mais ne pas faire échouer la création de tâche
                    // Vous pouvez ajouter un logger ici si nécessaire
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

    #[Route('/{id}', name: 'task_show', methods: ['GET'])]
    public function show(Task $task): Response
    {
        $user = $this->getUser();

        // accès si propriétaire du projet ou assignee de la tâche
        if ($task->getProject()->getOwner() !== $user && $task->getAssignee() !== $user) {
            $this->addFlash('error', 'Vous n\'avez pas l\'autorisation de consulter cette tâche.');
            return $this->redirectToRoute('project_index');
        }

        $isOwner = $user === $task->getProject()->getOwner();

        return $this->render('task/show.html.twig', [
            'task' => $task,
            'isOwner' => $isOwner,
        ]);
    }

    #[Route('/{id}/edit', name: 'task_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Task $task, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();

        // propriétaire du projet ou assignee
        if ($task->getAssignee() !== $user && $task->getProject()->getOwner() !== $user) {
            $this->addFlash('error', 'Vous ne pouvez modifier que vos propres tâches.');
            return $this->redirectToRoute('task_show', ['id' => $task->getId()]);
        }

        // Stocker l'assignee original pour détecter les changements
        $originalAssignee = $task->getAssignee();

        $formOptions = ['user' => $user];

        if ($task->getAssignee() === $user && $task->getProject()->getOwner() !== $user) {
            $formOptions['is_collaborator'] = true; // désactiver certains champs
        }

        $form = $this->createForm(TaskType::class, $task, $formOptions);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $task->setUpdatedAt(new \DateTimeImmutable());
            $entityManager->flush();

            // Vérifier si l'assignation a changé
            $newAssignee = $task->getAssignee();
            
            // Envoyer notification si :
            // - Une nouvelle personne est assignée
            // - L'assignation a changé vers quelqu'un d'autre que l'utilisateur actuel
            // - Ce n'est pas un auto-assignement
            if ($newAssignee && 
                $newAssignee !== $user && 
                $originalAssignee !== $newAssignee) {
                
                try {
                    $this->notificationService->createTaskAssignedNotification($task, $user);
                } catch (\Exception $e) {
                    // Log l'erreur mais ne pas faire échouer la modification
                }
            }

            $this->addFlash('success', 'La tâche "' . $task->getTitle() . '" a été modifiée avec succès !');

            return $this->redirectToRoute('task_show', ['id' => $task->getId()]);
        }

        return $this->render('task/edit.html.twig', [
            'task' => $task,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'task_delete', methods: ['POST'])]
    public function delete(Request $request, Task $task, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();

        // seul le propriétaire du projet peut supprimer
        if ($task->getProject()->getOwner() !== $user) {
            $this->addFlash('error', 'Seul le propriétaire du projet peut supprimer une tâche.');
            return $this->redirectToRoute('task_show', ['id' => $task->getId()]);
        }

        if ($this->isCsrfTokenValid('delete' . $task->getId(), $request->request->get('_token'))) {
            $taskTitle = $task->getTitle();
            $project = $task->getProject();

            $entityManager->remove($task);
            $entityManager->flush();

            $this->addFlash('success', 'La tâche "' . $taskTitle . '" a été supprimée.');

            if ($project) {
                return $this->redirectToRoute('task_index', ['project' => $project->getId()]);
            }
            return $this->redirectToRoute('task_index');
        }

        $this->addFlash('error', 'Token CSRF invalide. La suppression a échoué.');
        return $this->redirectToRoute('task_show', ['id' => $task->getId()]);
    }

    #[Route('/{id}/toggle-status', name: 'task_toggle_status', methods: ['POST'])]
    public function toggleStatus(Request $request, Task $task, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();

        // propriétaire du projet ou assignee
        if ($task->getAssignee() !== $user && $task->getProject()->getOwner() !== $user) {
            $this->addFlash('error', 'Vous ne pouvez modifier que les tâches qui vous sont assignées.');
            return $this->redirectToRoute('task_show', ['id' => $task->getId()]);
        }

        if ($this->isCsrfTokenValid('toggle-status' . $task->getId(), $request->request->get('_token'))) {
            switch ($task->getStatus()) {
                case Task::STATUS_TODO: $newStatus = Task::STATUS_IN_PROGRESS; break;
                case Task::STATUS_IN_PROGRESS: $newStatus = Task::STATUS_COMPLETED; break;
                case Task::STATUS_COMPLETED: $newStatus = Task::STATUS_TODO; break;
                default: $newStatus = Task::STATUS_TODO;
            }

            $task->setStatus($newStatus);
            $task->setUpdatedAt(new \DateTimeImmutable());
            $entityManager->flush();

            if ($request->isXmlHttpRequest()) {
                return new JsonResponse([
                    'success' => true,
                    'status' => $newStatus,
                    'status_label' => $task->getStatusLabel(),
                    'message' => 'Statut mis à jour avec succès'
                ]);
            }

            $this->addFlash('success', 'Statut mis à jour avec succès');
            return $this->redirectToRoute('task_show', ['id' => $task->getId()]);
        }

        if ($request->isXmlHttpRequest()) {
            return new JsonResponse(['success' => false, 'message' => 'Token CSRF invalide'], 400);
        }

        $this->addFlash('error', 'Token CSRF invalide. Le changement de statut a échoué.');
        return $this->redirectToRoute('task_show', ['id' => $task->getId()]);
    }

    #[Route('/project/{id}/quick-add', name: 'task_quick_add', methods: ['POST'])]
    public function quickAdd(Request $request, Project $project, EntityManagerInterface $entityManager): JsonResponse
    {
        $user = $this->getUser();

        // seul le propriétaire peut ajouter rapidement
        if ($project->getOwner() !== $user) {
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