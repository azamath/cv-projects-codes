<?php

namespace App\Repository;

use App\Entity\SigningMeta;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method SigningMeta|null find($id, $lockMode = null, $lockVersion = null)
 * @method SigningMeta|null findOneBy(array $criteria, array $orderBy = null)
 * @method SigningMeta[]    findAll()
 * @method SigningMeta[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SigningMetaRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SigningMeta::class);
    }

    // /**
    //  * @return SigningMeta[] Returns an array of SigningMeta objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('s.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?SigningMeta
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
