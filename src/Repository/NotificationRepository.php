<?php

namespace App\Repository;

use App\Entity\Notification;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Notification>
 *
 * @method Notification|null find($id, $lockMode = null, $lockVersion = null)
 * @method Notification|null findOneBy(array $criteria, array $orderBy = null)
 * @method Notification[]    findAll()
 * @method Notification[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class NotificationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Notification::class);
    }

    /**
     * Compte les notifications non lues pour un utilisateur donné.
     *
     * @param User $user
     * @return int
     */
    public function countUnreadForUser(User $user): int
    {
        return (int) $this->createQueryBuilder('n')
            ->select('COUNT(n.id)')
            ->where('n.recipient = :user')
            ->andWhere('n.status = :unread_status')
            ->setParameter('user', $user)
            ->setParameter('unread_status', Notification::STATUS_UNREAD)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Marque toutes les notifications d'un utilisateur comme lues.
     *
     * @param User $user
     * @return int Le nombre de notifications mises à jour.
     */
    public function markAllAsReadForUser(User $user): int
    {
        return $this->createQueryBuilder('n')
            ->update()
            ->set('n.status', ':read_status')
            ->set('n.readAt', ':read_at')
            ->where('n.recipient = :user')
            ->andWhere('n.status = :unread_status')
            ->setParameter('read_status', Notification::STATUS_READ)
            ->setParameter('read_at', new \DateTimeImmutable())
            ->setParameter('user', $user)
            ->setParameter('unread_status', Notification::STATUS_UNREAD)
            ->getQuery()
            ->execute();
    }

    /**
     * Supprime toutes les notifications lues pour un utilisateur.
     *
     * @param User $user
     * @return int Le nombre de notifications supprimées.
     */
    public function deleteReadForUser(User $user): int
    {
        return $this->createQueryBuilder('n')
            ->delete()
            ->where('n.recipient = :user')
            ->andWhere('n.status = :read_status')
            ->setParameter('user', $user)
            ->setParameter('read_status', Notification::STATUS_READ)
            ->getQuery()
            ->execute();
    }
}