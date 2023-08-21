<?php

namespace App\Repository;

use App\Entity\MarginCache;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method MarginCache|null find($id, $lockMode = null, $lockVersion = null)
 * @method MarginCache|null findOneBy(array $criteria, array $orderBy = null)
 * @method MarginCache[]    findAll()
 * @method MarginCache[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class MarginCacheRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MarginCache::class);
    }

    /**
     * @param string $hash
     * @return MarginCache|null Returns MarginCache by hash
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function findOneByHash(string $hash): ?MarginCache
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.marginHash = :val')
            ->setParameter('val', $hash)
            ->getQuery()
            ->getOneOrNullResult();
    }


    /*
    public function findOneBySomeField($value): ?MarginCache
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
