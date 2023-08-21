<?php

namespace App\Repository;

use App\Entity\PermissionsAggregatedData;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method PermissionsAggregatedData|null find($id, $lockMode = null, $lockVersion = null)
 * @method PermissionsAggregatedData|null findOneBy(array $criteria, array $orderBy = null)
 * @method PermissionsAggregatedData[]    findAll()
 * @method PermissionsAggregatedData[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PermissionsAggregatedDataRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PermissionsAggregatedData::class);
    }

    // /**
    //  * @return PermissionsAggregatedData[] Returns an array of PermissionsAggregatedData objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('p.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?PermissionsAggregatedData
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
