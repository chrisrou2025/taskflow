<?php

namespace App\Repository;

use App\Entity\Task;
use App\Entity\User;
use App\Entity\Project;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Task>
 */
class TaskRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Task::class);
    }

    /**
     * Trouve les tâches récentes d'un utilisateur
     */
    public function findRecentTasksByUser(User $user, int $limit = 10): array
    {
        return $this->createQueryBuilder('t')
            ->innerJoin('t.project', 'p')
            ->where('p.owner = :user')
            ->setParameter('user', $user)
            ->orderBy('t.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Compte les tâches par statut pour un utilisateur
     */
    public function getTasksCountByStatusForUser(User $user): array
    {
        $result = $this->createQueryBuilder('t')
            ->select('t.status, COUNT(t.id) as count')
            ->innerJoin('t.project', 'p')
            ->where('p.owner = :user')
            ->setParameter('user', $user)
            ->groupBy('t.status')
            ->getQuery()
            ->getResult();

        // Transformation pour avoir un format plus pratique
        $counts = [
            Task::STATUS_TODO => 0,
            Task::STATUS_IN_PROGRESS => 0,
            Task::STATUS_COMPLETED => 0,
        ];

        foreach ($result as $row) {
            $counts[$row['status']] = (int) $row['count'];
        }

        return $counts;
    }

    /**
     * Trouve les tâches en retard d'un utilisateur
     */
    public function findOverdueTasksByUser(User $user): array
    {
        return $this->createQueryBuilder('t')
            ->innerJoin('t.project', 'p')
            ->where('p.owner = :user')
            ->andWhere('t.dueDate < :now')
            ->andWhere('t.status != :completed')
            ->setParameter('user', $user)
            ->setParameter('now', new \DateTime())
            ->setParameter('completed', Task::STATUS_COMPLETED)
            ->orderBy('t.dueDate', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les tâches d'un projet avec filtres optionnels
     */
    public function findByProjectWithFilters(Project $project, ?string $status = null, ?string $priority = null): array
    {
        $qb = $this->createQueryBuilder('t')
            ->where('t.project = :project')
            ->setParameter('project', $project);

        if ($status) {
            $qb->andWhere('t.status = :status')
               ->setParameter('status', $status);
        }

        if ($priority) {
            $qb->andWhere('t.priority = :priority')
               ->setParameter('priority', $priority);
        }

        return $qb->orderBy('t.createdAt', 'DESC')
                  ->getQuery()
                  ->getResult();
    }

    /**
     * Trouve les tâches à faire aujourd'hui pour un utilisateur
     */
    public function findTasksDueTodayByUser(User $user): array
    {
        $today = new \DateTime();
        $today->setTime(23, 59, 59); // Fin de journée

        $startOfDay = new \DateTime();
        $startOfDay->setTime(0, 0, 0); // Début de journée

        return $this->createQueryBuilder('t')
            ->innerJoin('t.project', 'p')
            ->where('p.owner = :user')
            ->andWhere('t.dueDate BETWEEN :start AND :end')
            ->andWhere('t.status != :completed')
            ->setParameter('user', $user)
            ->setParameter('start', $startOfDay)
            ->setParameter('end', $today)
            ->setParameter('completed', Task::STATUS_COMPLETED)
            ->orderBy('t.priority', 'DESC')
            ->addOrderBy('t.dueDate', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les tâches par priorité pour un utilisateur
     */
    public function findByPriorityAndUser(string $priority, User $user): array
    {
        return $this->createQueryBuilder('t')
            ->innerJoin('t.project', 'p')
            ->where('p.owner = :user')
            ->andWhere('t.priority = :priority')
            ->andWhere('t.status != :completed')
            ->setParameter('user', $user)
            ->setParameter('priority', $priority)
            ->setParameter('completed', Task::STATUS_COMPLETED)
            ->orderBy('t.dueDate', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Statistiques avancées pour un projet
     */
    public function getProjectStatistics(Project $project): array
    {
        // Nombre de tâches par statut
        $statusStats = $this->createQueryBuilder('t')
            ->select('t.status, COUNT(t.id) as count')
            ->where('t.project = :project')
            ->setParameter('project', $project)
            ->groupBy('t.status')
            ->getQuery()
            ->getResult();

        // Nombre de tâches par priorité
        $priorityStats = $this->createQueryBuilder('t')
            ->select('t.priority, COUNT(t.id) as count')
            ->where('t.project = :project')
            ->setParameter('project', $project)
            ->groupBy('t.priority')
            ->getQuery()
            ->getResult();

        // Temps moyen de completion (calculé en PHP)
        $completedTasks = $this->createQueryBuilder('t')
            ->where('t.project = :project')
            ->andWhere('t.status = :completed')
            ->andWhere('t.completedAt IS NOT NULL')
            ->setParameter('project', $project)
            ->setParameter('completed', Task::STATUS_COMPLETED)
            ->getQuery()
            ->getResult();

        $totalDays = 0;
        $count = 0;
        foreach ($completedTasks as $task) {
            if ($task->getCompletedAt() && $task->getCreatedAt()) {
                $diff = $task->getCreatedAt()->diff($task->getCompletedAt());
                $totalDays += $diff->days;
                $count++;
            }
        }

        $avgCompletionDays = $count > 0 ? round($totalDays / $count, 1) : 0;

        return [
            'status_stats' => $statusStats,
            'priority_stats' => $priorityStats,
            'avg_completion_days' => $avgCompletionDays,
        ];
    }

    /**
     * Statistiques globales des tâches par statut
     */
    public function getGlobalTasksByStatus(): array
    {
        $result = $this->createQueryBuilder('t')
            ->select('t.status, COUNT(t.id) as count')
            ->groupBy('t.status')
            ->getQuery()
            ->getResult();

        $counts = [
            'todo' => 0,
            'in_progress' => 0,
            'completed' => 0,
        ];

        foreach ($result as $row) {
            $counts[$row['status']] = (int) $row['count'];
        }

        return $counts;
    }

    /**
     * Pagination des tâches pour l'admin
     */
    public function findTasksWithPaginationForAdmin(int $page, int $limit, string $status = '', string $priority = ''): array
    {
        $qb = $this->createQueryBuilder('t')
            ->innerJoin('t.project', 'p')
            ->innerJoin('p.owner', 'u')
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit)
            ->orderBy('t.createdAt', 'DESC');

        if (!empty($status)) {
            $qb->andWhere('t.status = :status')
               ->setParameter('status', $status);
        }

        if (!empty($priority)) {
            $qb->andWhere('t.priority = :priority')
               ->setParameter('priority', $priority);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Compte des tâches avec filtres pour l'admin
     */
    public function countTasksWithFiltersForAdmin(string $status = '', string $priority = ''): int
    {
        $qb = $this->createQueryBuilder('t')
            ->select('COUNT(t.id)');

        if (!empty($status)) {
            $qb->andWhere('t.status = :status')
               ->setParameter('status', $status);
        }

        if (!empty($priority)) {
            $qb->andWhere('t.priority = :priority')
               ->setParameter('priority', $priority);
        }

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * Statistiques des tâches par priorité
     */
    public function getTasksByPriorityStats(): array
    {
        $result = $this->createQueryBuilder('t')
            ->select('t.priority, COUNT(t.id) as count')
            ->groupBy('t.priority')
            ->getQuery()
            ->getResult();

        $formatted = [];
        foreach ($result as $row) {
            $formatted[] = [
                'priority' => ucfirst($row['priority']),
                'count' => (int) $row['count']
            ];
        }

        return $formatted;
    }

    /**
     * Statistiques de taux de completion
     */
    public function getCompletionRateStats(): array
    {
        $result = $this->createQueryBuilder('t')
            ->select("SUBSTRING(t.createdAt, 1, 7) as month, 
                     COUNT(t.id) as total,
                     SUM(CASE WHEN t.status = 'completed' THEN 1 ELSE 0 END) as completed")
            ->where('t.createdAt >= :sixMonthsAgo')
            ->setParameter('sixMonthsAgo', new \DateTime('-6 months'))
            ->groupBy('month')
            ->orderBy('month', 'ASC')
            ->getQuery()
            ->getResult();

        $formatted = [];
        foreach ($result as $row) {
            $total = (int) $row['total'];
            $completed = (int) $row['completed'];
            $rate = $total > 0 ? round(($completed / $total) * 100, 1) : 0;

            $formatted[] = [
                'period' => \DateTime::createFromFormat('Y-m', $row['month'])->format('M Y'),
                'rate' => $rate,
                'total' => $total,
                'completed' => $completed
            ];
        }

        return $formatted;
    }

    /**
     * Moyenne des tâches par projet
     */
    public function getAverageTasksPerProject(): float
    {
        $result = $this->createQueryBuilder('t')
            ->select('COUNT(t.id) as total_tasks, COUNT(DISTINCT t.project) as total_projects')
            ->getQuery()
            ->getSingleResult();

        $totalTasks = (int) $result['total_tasks'];
        $totalProjects = (int) $result['total_projects'];

        return $totalProjects > 0 ? round($totalTasks / $totalProjects, 1) : 0;
    }

    /**
     * Recherche de tâches par titre pour un utilisateur
     */
    public function searchByTitleForUser(string $query, User $user): array
    {
        return $this->createQueryBuilder('t')
            ->innerJoin('t.project', 'p')
            ->where('p.owner = :user')
            ->andWhere('t.title LIKE :query OR t.description LIKE :query')
            ->setParameter('user', $user)
            ->setParameter('query', '%' . $query . '%')
            ->orderBy('t.title', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Tâches récemment mises à jour pour un utilisateur
     */
    public function findRecentlyUpdatedByUser(User $user, int $limit = 10): array
    {
        return $this->createQueryBuilder('t')
            ->innerJoin('t.project', 'p')
            ->where('p.owner = :user')
            ->andWhere('t.updatedAt IS NOT NULL')
            ->setParameter('user', $user)
            ->orderBy('t.updatedAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Tâches complétées cette semaine pour un utilisateur
     */
    public function findCompletedThisWeekByUser(User $user): array
    {
        $startOfWeek = new \DateTime();
        $startOfWeek->modify('monday this week')->setTime(0, 0, 0);
        
        return $this->createQueryBuilder('t')
            ->innerJoin('t.project', 'p')
            ->where('p.owner = :user')
            ->andWhere('t.status = :completed')
            ->andWhere('t.completedAt >= :startOfWeek')
            ->setParameter('user', $user)
            ->setParameter('completed', Task::STATUS_COMPLETED)
            ->setParameter('startOfWeek', $startOfWeek)
            ->orderBy('t.completedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Tâches sans échéance pour un utilisateur
     */
    public function findWithoutDueDateByUser(User $user): array
    {
        return $this->createQueryBuilder('t')
            ->innerJoin('t.project', 'p')
            ->where('p.owner = :user')
            ->andWhere('t.dueDate IS NULL')
            ->andWhere('t.status != :completed')
            ->setParameter('user', $user)
            ->setParameter('completed', Task::STATUS_COMPLETED)
            ->orderBy('t.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}