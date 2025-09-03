<?php

namespace App\Controller;

use App\Entity\Task;
use App\Entity\Project;
use App\Form\TaskType;
use App\Repository\TaskRepository;
use App\Repository\ProjectRepository;
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
    #[Route('/', name: 'task_index', methods: ['GET'])]
    public function index(
        Request $request,
        TaskRepository $taskRepository,
        ProjectRepository $projectRepository
    ): Response {
        // Récupération des paramètres de filtrage
        $projectId = $request->query->get('project');
        $status = $request->query->get('status');
        $priority = $request->query->get('priority');

        // Récupération des projets de l'utilisateur pour le filtre
        $userProjects = $projectRepository->findBy(['owner' => $this->getUser()]);

        $project = null;
        $tasks = [];

        if ($projectId) {
            $project = $projectRepository->find($projectId);
            if ($project && $project->getOwner() === $this->getUser()) {
                $tasks = $taskRepository->findByProjectWithFilters($project, $status, $priority);
            }
        } else {
            // Si aucun projet spécifié, récupérer toutes les tâches de l'utilisateur
            $tasks = $taskRepository->findRecentTasksByUser($this->getUser(), 50);

            // Appliquer les filtres si nécessaire
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

        // Si un projet est spécifié dans l'URL, l'associer à la tâche
        $projectId = $request->query->get('project');
        if ($projectId) {
            $project = $projectRepository->find($projectId);
            if ($project && $project->getOwner() === $this->getUser()) {
                $task->setProject($project);
            }
        }

        $form = $this->createForm(TaskType::class, $task, [
            'user' => $this->getUser()
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($task);
            $entityManager->flush();

            $this->addFlash('success', 'La tâche "' . $task->getTitle() . '" a été créée avec succès !');

            // Redirection vers la liste des tâches du projet ou générale
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
        // Vérification que l'utilisateur est propriétaire de la tâche
        $this->denyAccessUnlessGranted('TASK_VIEW', $task);

        return $this->render('task/show.html.twig', [
            'task' => $task,
        ]);
    }

    #[Route('/{id}/edit', name: 'task_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Task $task, EntityManagerInterface $entityManager): Response
    {
        // Vérification que l'utilisateur est propriétaire de la tâche
        $this->denyAccessUnlessGranted('TASK_EDIT', $task);

        $form = $this->createForm(TaskType::class, $task, [
            'user' => $this->getUser()
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Mise à jour de la date de modification
            $task->setUpdatedAt(new \DateTimeImmutable());

            $entityManager->flush();

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
        // Vérification que l'utilisateur est propriétaire de la tâche
        $this->denyAccessUnlessGranted('TASK_DELETE', $task);

        if ($this->isCsrfTokenValid('delete' . $task->getId(), $request->request->get('_token'))) {
            $taskTitle = $task->getTitle();
            $project = $task->getProject();

            $entityManager->remove($task);
            $entityManager->flush();

            $this->addFlash('success', 'La tâche "' . $taskTitle . '" a été supprimée.');

            // Redirection vers la liste des tâches du projet
            if ($project) {
                return $this->redirectToRoute('task_index', ['project' => $project->getId()]);
            }
            return $this->redirectToRoute('task_index');
        } else {
            $this->addFlash('error', 'Token CSRF invalide. La suppression a échoué.');
        }

        return $this->redirectToRoute('task_show', ['id' => $task->getId()]);
    }

    #[Route('/{id}/toggle-status', name: 'task_toggle_status', methods: ['POST'])]
    public function toggleStatus(Request $request, Task $task, EntityManagerInterface $entityManager): Response
    {
        // Vérification que l'utilisateur est propriétaire de la tâche
        $this->denyAccessUnlessGranted('TASK_EDIT', $task);

        if ($this->isCsrfTokenValid('toggle-status' . $task->getId(), $request->request->get('_token'))) {
            // Basculer entre les statuts
            switch ($task->getStatus()) {
                case Task::STATUS_TODO:
                    $newStatus = Task::STATUS_IN_PROGRESS;
                    break;
                case Task::STATUS_IN_PROGRESS:
                    $newStatus = Task::STATUS_COMPLETED;
                    break;
                case Task::STATUS_COMPLETED:
                    $newStatus = Task::STATUS_TODO;
                    break;
                default:
                    $newStatus = Task::STATUS_TODO;
            }

            $task->setStatus($newStatus);
            $task->setUpdatedAt(new \DateTimeImmutable());

            $entityManager->flush();

            // Vérifier si c'est une requête AJAX
            if ($request->isXmlHttpRequest()) {
                return new JsonResponse([
                    'success' => true,
                    'status' => $newStatus,
                    'status_label' => $task->getStatusLabel(),
                    'message' => 'Statut mis à jour avec succès'
                ]);
            }

            // Pour les requêtes normales, ajouter un flash message et rediriger
            $this->addFlash('success', 'Statut mis à jour avec succès');

            // Rediriger vers la page précédente ou la page de la tâche
            $referer = $request->headers->get('referer');
            if ($referer) {
                return $this->redirect($referer);
            }

            return $this->redirectToRoute('task_show', ['id' => $task->getId()]);
        }

        // Token CSRF invalide
        if ($request->isXmlHttpRequest()) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Token CSRF invalide'
            ], 400);
        }

        $this->addFlash('error', 'Token CSRF invalide. Le changement de statut a échoué.');
        return $this->redirectToRoute('task_show', ['id' => $task->getId()]);
    }

    #[Route('/{id}/duplicate', name: 'task_duplicate', methods: ['POST'])]
    public function duplicate(Request $request, Task $task, EntityManagerInterface $entityManager): Response
    {
        // Vérification que l'utilisateur est propriétaire de la tâche
        $this->denyAccessUnlessGranted('TASK_VIEW', $task);

        if ($this->isCsrfTokenValid('duplicate' . $task->getId(), $request->request->get('_token'))) {
            // Création d'une copie de la tâche
            $newTask = new Task();
            $newTask->setTitle($task->getTitle() . ' (Copie)');
            $newTask->setDescription($task->getDescription());
            $newTask->setPriority($task->getPriority());
            $newTask->setProject($task->getProject());
            // La nouvelle tâche commence avec le statut "À faire"
            $newTask->setStatus(Task::STATUS_TODO);

            // Si la tâche originale a une échéance, l'ajouter à la copie avec +1 jour
            if ($task->getDueDate()) {
                $originalDate = $task->getDueDate();
                // Créer une nouvelle instance DateTime pour pouvoir utiliser add()
                $newDueDate = new \DateTime($originalDate->format('Y-m-d H:i:s'));
                $newDueDate->add(new \DateInterval('P1D'));
                $newTask->setDueDate($newDueDate);
            }

            $entityManager->persist($newTask);
            $entityManager->flush();

            $this->addFlash('success', 'La tâche a été dupliquée avec succès !');

            return $this->redirectToRoute('task_show', ['id' => $newTask->getId()]);
        } else {
            $this->addFlash('error', 'Token CSRF invalide. La duplication a échoué.');
        }

        return $this->redirectToRoute('task_show', ['id' => $task->getId()]);
    }

    #[Route('/project/{id}/quick-add', name: 'task_quick_add', methods: ['POST'])]
    public function quickAdd(
        Request $request,
        Project $project,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        // Vérification que l'utilisateur est propriétaire du projet
        $this->denyAccessUnlessGranted('PROJECT_VIEW', $project);

        $title = $request->request->get('title');

        if (empty(trim($title))) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Le titre ne peut pas être vide'
            ], 400);
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

        return new JsonResponse([
            'success' => false,
            'message' => 'Token CSRF invalide'
        ], 400);
    }
}
