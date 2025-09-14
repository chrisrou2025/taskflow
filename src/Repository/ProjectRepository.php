<?php

namespace App\Repository;

use App\Entity\Project;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\User\UserInterface;

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
     * Récupère les projets dont l'utilisateur est propriétaire ou collaborateur.
     *
     * @return Project[]
     */
    public function findProjectsByUser(UserInterface $user): array
    {
        return $this->createQueryBuilder('p')
            ->leftJoin('p.tasks', 't')
            ->where('p.owner = :user')
            ->orWhere('t.assignee = :user')
            ->setParameter('user', $user)
            ->groupBy('p.id')
            ->orderBy('p.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère les projets dont l'utilisateur est propriétaire ou collaborateur
     * et retourne un QueryBuilder.
     */
    public function findProjectsByUserQueryBuilder(UserInterface $user): \Doctrine\ORM\QueryBuilder
    {
        return $this->createQueryBuilder('p')
            ->leftJoin('p.tasks', 't')
            ->where('p.owner = :user')
            ->orWhere('t.assignee = :user')
            ->setParameter('user', $user)
            ->groupBy('p.id')
            ->orderBy('p.createdAt', 'DESC');
    }

    /**
     * Trouve les projets sur lesquels un utilisateur collabore.
     * Un projet est considéré comme collaboratif si l'utilisateur y a au moins une tâche assignée,
     * mais n'est PAS le propriétaire du projet.
     */
    public function findCollaborativeProjects(User $user): array
    {
        return $this->createQueryBuilder('p') // 'p' est l'alias pour l'entité Project
            ->select('p')
            ->distinct() // Assure que chaque projet est retourné une seule fois
            ->innerJoin('p.tasks', 't') // Fait une jointure avec les tâches associées au projet
            ->where('t.assignee = :user') // Condition 1: La tâche doit être assignée à l'utilisateur
            ->andWhere('p.owner != :user') // Condition 2: Le propriétaire du projet ne doit PAS être cet utilisateur
            ->setParameter('user', $user) // Lie la variable :user à l'objet User
            ->orderBy('p.createdAt', 'DESC') // Trie les projets du plus récent au plus ancien
            ->getQuery()
            ->getResult();
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
            ->select("SUBSTRING(p.createdAt, 1, 7) as month, COUNT(p.id) as count")
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
     * Récupère les projets dont l'utilisateur est propriétaire ou collaborateur.
     */
    public function findProjectsByUserWithCollaborations($user): array
    {
        return $this->createQueryBuilder('p')
            ->leftJoin('p.tasks', 't')
            ->where('p.owner = :user OR t.assignee = :user')
            ->setParameter('user', $user)
            ->groupBy('p.id')
            ->orderBy('p.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Compte le nombre de projets dont l'utilisateur est propriétaire ou collaborateur
     */
    public function countProjectsByUserWithCollaborations(User $user): int
    {
        return (int) $this->createQueryBuilder('p')
            ->select('COUNT(DISTINCT p.id)')
            ->leftJoin('p.tasks', 't')
            ->where('p.owner = :user OR t.assignee = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getSingleScalarResult();
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
            ->setParameter('user', $user)
            ->getQuery()
            ->getSingleScalarResult();

        return (float) $result;
    }
}
