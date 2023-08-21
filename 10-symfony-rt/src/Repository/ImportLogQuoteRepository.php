<?php

namespace App\Repository;

use App\Entity\ImportLogQuote;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method ImportLogQuote|null find($id, $lockMode = null, $lockVersion = null)
 * @method ImportLogQuote|null findOneBy(array $criteria, array $orderBy = null)
 * @method ImportLogQuote[]    findAll()
 * @method ImportLogQuote[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ImportLogQuoteRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ImportLogQuote::class);
    }

    // /**
    //  * @return ImportLogQuote[] Returns an array of ImportLogQuote objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('i')
            ->andWhere('i.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('i.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?ImportLogQuote
    {
        return $this->createQueryBuilder('i')
            ->andWhere('i.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
