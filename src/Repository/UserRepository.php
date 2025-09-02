<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;

/**
 * @extends ServiceEntityRepository<User>
 */
class UserRepository extends ServiceEntityRepository implements PasswordUpgraderInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    /**
     * Méthode requise pour PasswordUpgraderInterface
     */
    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', $user::class));
        }

        $user->setPassword($newHashedPassword);
        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->flush();
    }

    /**
     * Compte les utilisateurs actifs (ayant créé au moins un projet)
     */
    public function countActiveUsers(): int
    {
        return (int) $this->createQueryBuilder('u')
            ->select('COUNT(DISTINCT u.id)')
            ->innerJoin('u.projects', 'p')
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Trouve les utilisateurs les plus actifs
     */
    public function findMostActiveUsers(int $limit = 10): array
    {
        return $this->createQueryBuilder('u')
            ->leftJoin('u.projects', 'p')
            ->leftJoin('p.tasks', 't')
            ->select('u', 'COUNT(DISTINCT p.id) as projectCount', 'COUNT(t.id) as taskCount')
            ->groupBy('u.id')
            ->orderBy('projectCount', 'DESC')
            ->addOrderBy('taskCount', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Pagination des utilisateurs avec recherche
     */
    public function findUsersWithPagination(int $page, int $limit, string $search = ''): array
    {
        $qb = $this->createQueryBuilder('u')
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit)
            ->orderBy('u.createdAt', 'DESC');

        if (!empty($search)) {
            $qb->andWhere('u.firstName LIKE :search OR u.lastName LIKE :search OR u.email LIKE :search')
               ->setParameter('search', '%' . $search . '%');
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Compte des utilisateurs avec recherche
     */
    public function countUsersWithSearch(string $search = ''): int
    {
        $qb = $this->createQueryBuilder('u')
            ->select('COUNT(u.id)');

        if (!empty($search)) {
            $qb->andWhere('u.firstName LIKE :search OR u.lastName LIKE :search OR u.email LIKE :search')
               ->setParameter('search', '%' . $search . '%');
        }

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * Statistiques de croissance des utilisateurs
     */
    public function getUserGrowthStats(): array
    {
        $result = $this->createQueryBuilder('u')
            ->select("DATE_FORMAT(u.createdAt, '%Y-%m') as month, COUNT(u.id) as count")
            ->where('u.createdAt >= :sixMonthsAgo')
            ->setParameter('sixMonthsAgo', new \DateTime('-6 months'))
            ->groupBy('month')
            ->orderBy('month', 'ASC')
            ->getQuery()
            ->getResult();

        // Formatage pour les graphiques
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
     * Utilisateurs par rôle
     */
    public function getUsersByRole(): array
    {
        // Compter les administrateurs
        $admins = $this->createQueryBuilder('u')
            ->select('COUNT(u.id)')
            ->where('u.roles LIKE :adminRole')
            ->setParameter('adminRole', '%ROLE_ADMIN%')
            ->getQuery()
            ->getSingleScalarResult();

        // Compter les utilisateurs normaux
        $users = $this->createQueryBuilder('u')
            ->select('COUNT(u.id)')
            ->where('u.roles NOT LIKE :adminRole OR u.roles IS NULL')
            ->setParameter('adminRole', '%ROLE_ADMIN%')
            ->getQuery()
            ->getSingleScalarResult();

        return [
            'administrators' => (int) $admins,
            'users' => (int) $users
        ];
    }

    /**
     * Utilisateurs inactifs (sans projets depuis X jours)
     */
    public function findInactiveUsers(int $days = 30): array
    {
        return $this->createQueryBuilder('u')
            ->leftJoin('u.projects', 'p')
            ->where('u.createdAt < :threshold')
            ->andWhere('p.id IS NULL OR p.createdAt < :threshold')
            ->setParameter('threshold', new \DateTime("-$days days"))
            ->orderBy('u.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
    }
}