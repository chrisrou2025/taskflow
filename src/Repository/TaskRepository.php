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

        // Temps moyen de completion (en jours)
        $completionTime = $this->createQueryBuilder('t')
            ->select('AVG(DATEDIFF(t.completedAt, t.createdAt)) as avg_days')
            ->where('t.project = :project')
            ->andWhere('t.status = :completed')
            ->setParameter('project', $project)
            ->setParameter('completed', Task::STATUS_COMPLETED)
            ->getQuery()
            ->getSingleScalarResult();

        return [
            'status_stats' => $statusStats,
            'priority_stats' => $priorityStats,
            'avg_completion_days' => $completionTime ? round($completionTime, 1) : 0,
        ];
    }
}
