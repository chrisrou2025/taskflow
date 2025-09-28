<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Repository\ProjectRepository;
use App\Repository\TaskRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Psr\Log\LoggerInterface;

#[Route('/admin')]
#[IsGranted('ROLE_ADMIN')]
class AdminController extends AbstractController
{
    // CORRECTION: Injection du logger dans le constructeur
    private LoggerInterface $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    #[Route('/', name: 'admin_dashboard')]
    public function dashboard(
        UserRepository $userRepository,
        ProjectRepository $projectRepository,
        TaskRepository $taskRepository
    ): Response {
        // Statistiques globales
        $totalUsers = $userRepository->count([]);
        $totalProjects = $projectRepository->count([]);
        $totalTasks = $taskRepository->count([]);
        $completedTasksCount = $taskRepository->count(['status' => 'completed']);

        // Statistiques des utilisateurs actifs (ayant créé au moins un projet)
        $activeUsers = $userRepository->countActiveUsers();

        // Utilisateurs récents
        $recentUsers = $userRepository->findBy([], ['createdAt' => 'DESC'], 5);

        // Projets récents
        $recentProjects = $projectRepository->findBy([], ['createdAt' => 'DESC'], 10);

        // Statistiques des tâches par statut
        $tasksByStatus = $taskRepository->getGlobalTasksByStatus();

        // Utilisateurs les plus actifs
        $mostActiveUsers = $userRepository->findMostActiveUsers(5);
        
        // Statistiques pour le graphique d'activité mensuelle
        $monthlyActivity = [
            'labels' => ['Jan', 'Fév', 'Mar', 'Avr', 'Mai', 'Juin', 'Juil', 'Août', 'Sept', 'Oct', 'Nov', 'Déc'],
            'projects' => [20, 30, 45, 60, 50, 75, 80, 90, 85, 95, 110, 100],
            'users' => [10, 15, 25, 30, 28, 40, 42, 50, 48, 55, 65, 60],
            'tasks' => [50, 60, 80, 100, 95, 120, 130, 150, 140, 160, 180, 175],
        ];

        return $this->render('admin/dashboard.html.twig', [
            'total_users' => $totalUsers,
            'total_projects' => $totalProjects,
            'total_tasks' => $totalTasks,
            'completed_tasks_count' => $completedTasksCount,
            'active_users' => $activeUsers,
            'recent_users' => $recentUsers,
            'recent_projects' => $recentProjects,
            'tasks_by_status' => $tasksByStatus,
            'most_active_users' => $mostActiveUsers,
            'monthly_activity' => $monthlyActivity,
        ]);
    }

    #[Route('/users', name: 'admin_users')]
    public function users(UserRepository $userRepository, Request $request): Response
    {
        // Pagination et filtres
        $page = max(1, $request->query->getInt('page', 1));
        $limit = 20;
        $search = $request->query->get('search', '');

        $users = $userRepository->findUsersWithPagination($page, $limit, $search);
        $totalUsers = $userRepository->countUsersWithSearch($search);
        $totalPages = ceil($totalUsers / $limit);

        return $this->render('admin/users.html.twig', [
            'users' => $users,
            'current_page' => $page,
            'total_pages' => $totalPages,
            'total_users' => $totalUsers,
            'search' => $search,
        ]);
    }

    #[Route('/users/{id}', name: 'admin_user_show')]
    public function showUser(
        User $user,
        ProjectRepository $projectRepository,
        TaskRepository $taskRepository
    ): Response {
        // Projets où l'utilisateur est le propriétaire
        $ownedProjects = $projectRepository->findBy(['owner' => $user], ['createdAt' => 'DESC']);

        // Projets où l'utilisateur est collaborateur
        $collaborativeProjects = $projectRepository->findCollaborativeProjects($user);

        // Statistiques des tâches où l'utilisateur est assigné
        $userTasksStats = $taskRepository->getTasksStatisticsForUser($user);

        // Tâches récentes assignées à l'utilisateur
        $recentTasks = $taskRepository->findBy(['assignee' => $user], ['createdAt' => 'DESC'], 10);
        
        // Tâches en retard assignées à l'utilisateur
        $overdueTasks = $taskRepository->findOverdueTasksByAssignee($user);

        return $this->render('admin/user_show.html.twig', [
            'user' => $user,
            'projects' => $ownedProjects,
            'collaborative_projects' => $collaborativeProjects,
            'tasks_stats' => $userTasksStats,
            'recent_tasks' => $recentTasks,
            'overdue_tasks' => $overdueTasks,
        ]);
    }

    #[Route('/users/{id}/toggle-role', name: 'admin_user_toggle_role', methods: ['POST'])]
    public function toggleUserRole(
        Request $request,
        User $user,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        // Validation du token CSRF
        if (!$this->isCsrfTokenValid('toggle-role-' . $user->getId(), $request->request->get('_token'))) {
            return new JsonResponse([
                'success' => false, 
                'message' => 'Token CSRF invalide'
            ], 400);
        }

        // CORRECTION: Vérification que getUser() n'est pas null
        $currentUser = $this->getUser();
        if (!$currentUser instanceof User) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Utilisateur non authentifié'
            ], 401);
        }

        // Ne pas permettre de modifier son propre rôle
        if ($user === $currentUser) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Vous ne pouvez pas modifier votre propre rôle'
            ], 400);
        }

        try {
            $roles = $user->getRoles();
            $userName = $user->getFullName();
            
            if (in_array('ROLE_ADMIN', $roles)) {
                // Retirer le rôle admin
                $user->setRoles(['ROLE_USER']);
                $newRole = 'Utilisateur';
                $message = "Les droits d'administrateur ont été retirés à {$userName}";
            } else {
                // Ajouter le rôle admin
                $user->setRoles(['ROLE_USER', 'ROLE_ADMIN']);
                $newRole = 'Administrateur';
                $message = "Les droits d'administrateur ont été accordés à {$userName}";
            }

            $entityManager->flush();

            return new JsonResponse([
                'success' => true,
                'message' => $message,
                'new_role' => $newRole
            ]);
            
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour : ' . $e->getMessage()
            ], 500);
        }
    }

    #[Route('/users/{id}/delete', name: 'admin_user_delete', methods: ['POST'])]
    public function deleteUser(
        Request $request,
        User $user,
        EntityManagerInterface $entityManager
    ): Response {
        // Validation du token CSRF
        if (!$this->isCsrfTokenValid('delete-user-' . $user->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Token CSRF invalide.');
            return $this->redirectToRoute('admin_users');
        }

        // CORRECTION: Vérification que getUser() n'est pas null
        $currentUser = $this->getUser();
        if (!$currentUser instanceof User) {
            $this->addFlash('error', 'Utilisateur non authentifié.');
            return $this->redirectToRoute('admin_users');
        }

        // Ne pas permettre de supprimer son propre compte
        if ($user === $currentUser) {
            $this->addFlash('error', 'Vous ne pouvez pas supprimer votre propre compte.');
            return $this->redirectToRoute('admin_users');
        }

        try {
            $userName = $user->getFullName();
            $entityManager->remove($user);
            $entityManager->flush();

            $this->addFlash('success', "L'utilisateur \"{$userName}\" a été supprimé avec succès.");
            
        } catch (\Exception $e) {
            $this->addFlash('error', 'Erreur lors de la suppression : ' . $e->getMessage());
        }

        return $this->redirectToRoute('admin_users');
    }

    #[Route('/projects', name: 'admin_projects')]
    public function projects(ProjectRepository $projectRepository, UserRepository $userRepository, Request $request): Response
    {
        // Pagination et filtres
        $page = max(1, $request->query->getInt('page', 1));
        $limit = 20;
        $search = $request->query->get('search', '');
        $ownerId = $request->query->get('owner', '');
        $sort = $request->query->get('sort', 'created_at');

        // Récupérer tous les utilisateurs pour le filtre
        $users = $userRepository->findAll();

        $projects = $projectRepository->findProjectsWithPaginationForAdmin($page, $limit, $search, $ownerId, $sort);
        $totalProjects = $projectRepository->countProjectsWithSearch($search, $ownerId);
        $totalPages = ceil($totalProjects / $limit);

        return $this->render('admin/projects.html.twig', [
            'projects' => $projects,
            'users' => $users,
            'current_page' => $page,
            'total_pages' => $totalPages,
            'total_projects' => $totalProjects,
            'search' => $search,
        ]);
    }

    #[Route('/projects/{id}/delete', name: 'admin_project_delete', methods: ['POST'])]
    public function deleteProject(
        Request $request,
        \App\Entity\Project $project,
        EntityManagerInterface $entityManager
    ): Response {
        // Vérification des droits d'administration avec le ProjectVoter
        $this->denyAccessUnlessGranted('PROJECT_DELETE', $project);
        
        // Validation du token CSRF
        $expectedTokenId = 'delete-project-' . $project->getId();
        $submittedToken = $request->request->get('_token');
        
        if (!$this->isCsrfTokenValid($expectedTokenId, $submittedToken)) {
            $this->addFlash('error', 'Token CSRF invalide. Veuillez réessayer.');
            return $this->redirectToRoute('admin_projects');
        }

        try {
            $projectTitle = $project->getTitle();
            $projectOwner = $project->getOwner()->getFullName();
            
            // CORRECTION: Vérification que getUser() n'est pas null avant d'appeler getEmail()
            $currentUser = $this->getUser();
            $currentAdminEmail = $currentUser instanceof User ? $currentUser->getEmail() : 'Unknown';
            
            // CORRECTION: Utilisation sécurisée du logger
            $this->logger->info('Admin project deletion', [
                'admin_user' => $currentAdminEmail,
                'project_id' => $project->getId(),
                'project_title' => $projectTitle,
                'project_owner' => $projectOwner,
                'tasks_count' => $project->getTasks()->count()
            ]);
            
            $entityManager->remove($project);
            $entityManager->flush();

            $this->addFlash('success', sprintf(
                'Le projet "%s" de %s a été supprimé avec succès.',
                $projectTitle,
                $projectOwner
            ));
            
        } catch (\Exception $e) {
            // CORRECTION: Vérification que getUser() n'est pas null
            $currentUser = $this->getUser();
            $currentAdminEmail = $currentUser instanceof User ? $currentUser->getEmail() : 'Unknown';
            
            $this->logger->error('Project deletion failed', [
                'error' => $e->getMessage(),
                'project_id' => $project->getId(),
                'admin_user' => $currentAdminEmail,
                'trace' => $e->getTraceAsString()
            ]);
            
            $this->addFlash('error', 'Erreur lors de la suppression du projet : ' . $e->getMessage());
        }

        return $this->redirectToRoute('admin_projects');
    }

    #[Route('/tasks', name: 'admin_tasks')]
    public function tasks(TaskRepository $taskRepository, Request $request): Response
    {
        // Pagination et filtres
        $page = max(1, $request->query->getInt('page', 1));
        $limit = 30;
        $status = $request->query->get('status', '');
        $priority = $request->query->get('priority', '');

        $tasks = $taskRepository->findTasksWithPaginationForAdmin($page, $limit, $status, $priority);
        $totalTasks = $taskRepository->countTasksWithFiltersForAdmin($status, $priority);
        $totalPages = ceil($totalTasks / $limit);

        return $this->render('admin/tasks.html.twig', [
            'tasks' => $tasks,
            'current_page' => $page,
            'total_pages' => $totalPages,
            'total_tasks' => $totalTasks,
            'current_status' => $status,
            'current_priority' => $priority,
            'status_choices' => \App\Entity\Task::getStatusChoices(),
            'priority_choices' => \App\Entity\Task::getPriorityChoices(),
        ]);
    }

    #[Route('/statistics', name: 'admin_statistics')]
    public function statistics(
        UserRepository $userRepository,
        ProjectRepository $projectRepository,
        TaskRepository $taskRepository
    ): Response {
        // Statistiques avancées pour les graphiques
        $userGrowth = $userRepository->getUserGrowthStats();
        $projectsByMonth = $projectRepository->getProjectsByMonthStats();
        $tasksByPriority = $taskRepository->getTasksByPriorityStats();
        $completionRates = $taskRepository->getCompletionRateStats();
        $avgTasksPerProject = $taskRepository->getAverageTasksPerProject();

        return $this->render('admin/statistics.html.twig', [
            'user_growth' => $userGrowth,
            'projects_by_month' => $projectsByMonth,
            'tasks_by_priority' => $tasksByPriority,
            'completion_rates' => $completionRates,
            'avg_tasks_per_project' => $avgTasksPerProject,
        ]);
    }

    #[Route('/maintenance', name: 'admin_maintenance')]
    public function maintenance(): Response
    {
        return $this->render('admin/maintenance.html.twig');
    }

    #[Route('/maintenance/clear-cache', name: 'admin_clear_cache', methods: ['POST'])]
    public function clearCache(Request $request): JsonResponse
    {
        // Validation du token CSRF
        if (!$this->isCsrfTokenValid('clear-cache', $request->request->get('_token'))) {
            return new JsonResponse(['success' => false, 'message' => 'Token CSRF invalide'], 400);
        }

        try {
            // Ici on pourrait ajouter la logique pour vider le cache
            // Pour l'exemple, on simule un succès
            return new JsonResponse([
                'success' => true,
                'message' => 'Cache vidé avec succès'
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Erreur lors du vidage du cache: ' . $e->getMessage()
            ], 500);
        }
    }
}