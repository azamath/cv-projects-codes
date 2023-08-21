<?php

namespace App\Repository;

use App\Entity\QuoteProduct;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method QuoteProduct|null find($id, $lockMode = null, $lockVersion = null)
 * @method QuoteProduct|null findOneBy(array $criteria, array $orderBy = null)
 * @method QuoteProduct[]    findAll()
 * @method QuoteProduct[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class QuoteProductRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, QuoteProduct::class);
    }

    // /**
    //  * @return QuoteProduct[] Returns an array of QuoteProduct objects
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
    public function findOneBySomeField($value): ?QuoteProduct
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
