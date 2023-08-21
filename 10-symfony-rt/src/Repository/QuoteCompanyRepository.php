<?php

namespace App\Repository;

use App\Entity\QuoteCompany;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method QuoteCompany|null find($id, $lockMode = null, $lockVersion = null)
 * @method QuoteCompany|null findOneBy(array $criteria, array $orderBy = null)
 * @method QuoteCompany[]    findAll()
 * @method QuoteCompany[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class QuoteCompanyRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, QuoteCompany::class);
    }

    // /**
    //  * @return QuoteCompany[] Returns an array of QuoteCompany objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('q')
            ->andWhere('q.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('q.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?QuoteCompany
    {
        return $this->createQueryBuilder('q')
            ->andWhere('q.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
