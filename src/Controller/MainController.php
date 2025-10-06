<?php

namespace App\Controller;

use App\Repository\ProjectRepository;
use App\Repository\TaskRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class MainController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function home(): Response
    {
        // Si l'utilisateur est connecté, redirection vers le dashboard
        if ($this->getUser()) {
            return $this->redirectToRoute('app_dashboard');
        }

        return $this->render('main/home.html.twig');
    }

    #[Route('/dashboard', name: 'app_dashboard')]
    #[IsGranted('ROLE_USER')]
    public function dashboard(
        ProjectRepository $projectRepository,
        TaskRepository $taskRepository
    ): Response {
        $user = $this->getUser();

        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        try {
            // Récupération des projets de l'utilisateur connecté (propriétaire + collaborateur)
            $projects = $projectRepository->findProjectsByUserWithCollaborations($user);

            // Statistiques globales corrigées : inclut aussi les projets collaboratifs
            $totalProjects = $projectRepository->countProjectsByUserWithCollaborations($user);

            // Récupération des tâches récentes de l'utilisateur (assignées ou projets collaboratifs)
            $recentTasks = $taskRepository->findTasksByUser($user, 10);

            // Tâches par statut (corrigé pour inclure propriétaire + collaborateur + assignées)
            $tasksByStatus = $taskRepository->getTasksCountByStatusForUser($user);

            // Tâches en retard (corrigé idem)
            $overdueTasks = $taskRepository->findOverdueTasksByUser($user);
        } catch (\Exception $e) {
            // En cas d'erreur, initialiser avec des valeurs par défaut
            $projects = [];
            $totalProjects = 0;
            $recentTasks = [];
            $tasksByStatus = [
                'todo' => 0,
                'in_progress' => 0,
                'completed' => 0
            ];
            $overdueTasks = [];

            // Optionnel : logger l'erreur ou ajouter un message flash
            $this->addFlash('warning', 'Certaines données n\'ont pas pu être chargées.');
        }

        return $this->render('main/dashboard.html.twig', [
            'projects' => $projects,
            'total_projects' => $totalProjects,
            'recent_tasks' => $recentTasks,
            'tasks_by_status' => $tasksByStatus,
            'overdue_tasks' => $overdueTasks,
            'tasks' => $recentTasks, // Alias pour compatibilité
        ]);
    }

    /**
     * API : Récupération des tâches récentes en JSON
     */
    #[Route('/api/dashboard/recent-tasks', name: 'api_dashboard_recent_tasks', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function getRecentTasks(TaskRepository $taskRepository): JsonResponse
    {
        $user = $this->getUser();

        if (!$user) {
            return new JsonResponse(['error' => 'Utilisateur non authentifié'], 401);
        }

        try {
            $tasks = $taskRepository->findTasksByUser($user, 10);

            $data = array_map(function ($task) {
                return [
                    'id' => $task->getId(),
                    'title' => $task->getTitle(),
                    'project_title' => $task->getProject() ? $task->getProject()->getTitle() : 'Sans projet',
                    'status' => $task->getStatus(),
                    'status_label' => $task->getStatusLabel(),
                    'priority' => $task->getPriority(),
                    'priority_label' => $task->getPriorityLabel(),
                    'created_at' => $task->getCreatedAt()->format('c'),
                    'updated_at' => $task->getUpdatedAt() ? $task->getUpdatedAt()->format('c') : $task->getCreatedAt()->format('c'),
                ];
            }, $tasks);

            return new JsonResponse($data);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Erreur lors de la récupération des tâches'], 500);
        }
    }
}
