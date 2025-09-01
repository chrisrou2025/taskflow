<?php

namespace App\Controller;

use App\Repository\ProjectRepository;
use App\Repository\TaskRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

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
    public function dashboard(
        ProjectRepository $projectRepository,
        TaskRepository $taskRepository
    ): Response {
        $user = $this->getUser();

        // Récupération des projets de l'utilisateur connecté
        $projects = $projectRepository->findBy(
            ['owner' => $user],
            ['createdAt' => 'DESC'],
            5 // Limite aux 5 derniers projets
        );

        // Statistiques globales
        $totalProjects = $projectRepository->count(['owner' => $user]);

        // Récupération des tâches récentes de l'utilisateur
        $recentTasks = $taskRepository->findRecentTasksByUser($user, 10);

        // Tâches par statut
        $tasksByStatus = $taskRepository->getTasksCountByStatusForUser($user);

        // Tâches en retard
        $overdueTasks = $taskRepository->findOverdueTasksByUser($user);

        return $this->render('main/dashboard.html.twig', [
            'projects' => $projects,
            'total_projects' => $totalProjects,
            'recent_tasks' => $recentTasks,
            'tasks_by_status' => $tasksByStatus,
            'overdue_tasks' => $overdueTasks,
        ]);
    }
}
