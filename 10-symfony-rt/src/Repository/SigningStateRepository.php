<?php

namespace App\Repository;

use App\Entity\SigningState;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method SigningState|null find($id, $lockMode = null, $lockVersion = null)
 * @method SigningState|null findOneBy(array $criteria, array $orderBy = null)
 * @method SigningState[]    findAll()
 * @method SigningState[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SigningStateRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SigningState::class);
    }

    public function updateForSignings(array $signingIds, array $set): int
    {
        $queryBuilder = $this->getEntityManager()
            ->createQueryBuilder()
            ->update(SigningState::class, 'ss')
            ->where('ss.signingId IN (:signingIds)')
            ->setParameter('signingIds', $signingIds)
        ;

        foreach ($set as $field => $value) {
            $queryBuilder->set('ss.' . $field, ":$field");
            $queryBuilder->setParameter($field, $value);
        }

        return $queryBuilder->getQuery()->execute();
    }

    // /**
    //  * @return SigningState[] Returns an array of SigningState objects
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
    public function findOneBySomeField($value): ?SigningState
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
