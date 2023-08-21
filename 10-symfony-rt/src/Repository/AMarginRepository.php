<?php

namespace App\Repository;

use App\Enum\EMarginType;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;

abstract class AMarginRepository extends ServiceEntityRepository
{

    /**
     * @param EMarginType $type
     * @param int|null $limit
     * @return array Returns an array of Margin objects
     */
    public function findByType(EMarginType $type, int $limit = null): array
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.marginType = :val')
            ->setParameter('val', $type->value)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult()
        ;
    }

    /*
    public function findOneBySomeField($value): ?Margin
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
