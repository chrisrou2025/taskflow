<?php

namespace App\Repository;

use App\Entity\CollaborationRequest;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<CollaborationRequest>
 *
 * @method CollaborationRequest|null find($id, $lockMode = null, $lockVersion = null)
 * @method CollaborationRequest|null findOneBy(array $criteria, array $orderBy = null)
 * @method CollaborationRequest[]    findAll()
 * @method CollaborationRequest[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CollaborationRequestRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CollaborationRequest::class);
    }

    //    /**
    //     * @return CollaborationRequest[] Returns an array of CollaborationRequest objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('c')
    //            ->andWhere('c.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('c.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?CollaborationRequest
    //    {
    //        return $this->createQueryBuilder('c')
    //            ->andWhere('c.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}