<?php

namespace App\Repository;

use App\Entity\Project;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Project>
 */
class ProjectRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Project::class);
    }

    /**
     * Trouve les projets récents d'un utilisateur
     */
    public function findRecentByUser(User $user, int $limit = 10): array
    {
        return $this->createQueryBuilder('p')
            ->where('p.owner = :user')
            ->setParameter('user', $user)
            ->orderBy('p.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les projets avec leurs statistiques de tâches
     */
    public function findByUserWithTaskStats(User $user): array
    {
        return $this->createQueryBuilder('p')
            ->leftJoin('p.tasks', 't')
            ->where('p.owner = :user')
            ->setParameter('user', $user)
            ->orderBy('p.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Statistiques globales des projets pour un utilisateur
     */
    public function getProjectStatsByUser(User $user): array
    {
        // Nombre total de projets
        $totalProjects = $this->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->where('p.owner = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getSingleScalarResult();

        // Projets terminés (100% de progression)
        $completedProjects = $this->createQueryBuilder('p')
            ->leftJoin('p.tasks', 't')
            ->select('COUNT(DISTINCT p.id)')
            ->where('p.owner = :user')
            ->andWhere('NOT EXISTS (
                SELECT t2.id FROM App\Entity\Task t2 
                WHERE t2.project = p AND t2.status != :completed
            )')
            ->andWhere('EXISTS (
                SELECT t3.id FROM App\Entity\Task t3 
                WHERE t3.project = p
            )')
            ->setParameter('user', $user)
            ->setParameter('completed', 'completed')
            ->getQuery()
            ->getSingleScalarResult();

        // Projets actifs (avec des tâches en cours ou à faire)
        $activeProjects = $this->createQueryBuilder('p')
            ->select('COUNT(DISTINCT p.id)')
            ->leftJoin('p.tasks', 't')
            ->where('p.owner = :user')
            ->andWhere('EXISTS (
                SELECT t2.id FROM App\Entity\Task t2 
                WHERE t2.project = p AND t2.status IN (:active_statuses)
            )')
            ->setParameter('user', $user)
            ->setParameter('active_statuses', ['todo', 'in_progress'])
            ->getQuery()
            ->getSingleScalarResult();

        return [
            'total' => (int) $totalProjects,
            'completed' => (int) $completedProjects,
            'active' => (int) $activeProjects,
            'completion_rate' => $totalProjects > 0 ? round(($completedProjects / $totalProjects) * 100, 1) : 0
        ];
    }

    /**
     * Recherche de projets par titre pour un utilisateur
     */
    public function searchByTitleForUser(string $query, User $user): array
    {
        return $this->createQueryBuilder('p')
            ->where('p.owner = :user')
            ->andWhere('p.title LIKE :query OR p.description LIKE :query')
            ->setParameter('user', $user)
            ->setParameter('query', '%' . $query . '%')
            ->orderBy('p.title', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Projets les plus actifs (avec le plus de tâches récentes)
     */
    public function findMostActiveByUser(User $user, int $limit = 5): array
    {
        return $this->createQueryBuilder('p')
            ->leftJoin('p.tasks', 't')
            ->where('p.owner = :user')
            ->groupBy('p.id')
            ->orderBy('COUNT(t.id)', 'DESC')
            ->addOrderBy('p.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->setParameter('user', $user)
            ->getQuery()
            ->getResult();
    }

    /**
     * Projets en retard (avec des tâches en retard)
     */
    public function findWithOverdueTasksByUser(User $user): array
    {
        return $this->createQueryBuilder('p')
            ->where('p.owner = :user')
            ->andWhere('EXISTS (
                SELECT t.id FROM App\Entity\Task t 
                WHERE t.project = p 
                AND t.dueDate < :now 
                AND t.status != :completed
            )')
            ->setParameter('user', $user)
            ->setParameter('now', new \DateTime())
            ->setParameter('completed', 'completed')
            ->orderBy('p.title', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Pagination des projets pour l'admin
     */
    public function findProjectsWithPaginationForAdmin(int $page, int $limit, string $search = ''): array
    {
        $qb = $this->createQueryBuilder('p')
            ->innerJoin('p.owner', 'u')
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit)
            ->orderBy('p.createdAt', 'DESC');

        if (!empty($search)) {
            $qb->andWhere('p.title LIKE :search OR p.description LIKE :search OR u.firstName LIKE :search OR u.lastName LIKE :search')
                ->setParameter('search', '%' . $search . '%');
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Compte des projets avec recherche
     */
    public function countProjectsWithSearch(string $search = ''): int
    {
        $qb = $this->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->innerJoin('p.owner', 'u');

        if (!empty($search)) {
            $qb->andWhere('p.title LIKE :search OR p.description LIKE :search OR u.firstName LIKE :search OR u.lastName LIKE :search')
                ->setParameter('search', '%' . $search . '%');
        }

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * Statistiques des projets par mois
     */
    public function getProjectsByMonthStats(): array
    {
        $result = $this->createQueryBuilder('p')
            ->select("DATE_FORMAT(p.createdAt, '%Y-%m') as month, COUNT(p.id) as count")
            ->where('p.createdAt >= :sixMonthsAgo')
            ->setParameter('sixMonthsAgo', new \DateTime('-6 months'))
            ->groupBy('month')
            ->orderBy('month', 'ASC')
            ->getQuery()
            ->getResult();

        $formatted = [];
        foreach ($result as $row) {
            $formatted[] = [
                'period' => \DateTime::createFromFormat('Y-m', $row['month'])->format('M Y'),
                'count' => (int) $row['count']
            ];
        }

        return $formatted;
    }

    /**
     * Moyenne du nombre de tâches par projet pour un utilisateur
     */
    public function getAverageTasksPerProjectByUser(User $user): float
    {
        $result = $this->createQueryBuilder('p')
            ->select('AVG(task_count.cnt) as avg_tasks')
            ->leftJoin('(
                SELECT p2.id as project_id, COUNT(t.id) as cnt
                FROM App\Entity\Project p2
                LEFT JOIN App\Entity\Task t ON t.project = p2.id
                WHERE p2.owner = :user
                GROUP BY p2.id
            )', 'task_count', 'WITH', 'task_count.project_id = p.id')
            ->where('p.owner = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getSingleScalarResult();

        return $result ? round($result, 1) : 0.0;
    }
}
