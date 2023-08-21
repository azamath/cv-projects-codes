<?php

namespace App\Repository;

use App\Entity\Signing;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Signing|null find($id, $lockMode = null, $lockVersion = null)
 * @method Signing|null findOneBy(array $criteria, array $orderBy = null)
 * @method Signing[]    findAll()
 * @method Signing[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SigningRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Signing::class);
    }

    public function add(Signing $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Signing $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Get list if endCustomerNames for autocomplete matching the query
     *
     * @param string $query
     * @param int $maxResults
     * @return string[]
     */
    public function endCustomerAutocomplete(string $query, int $maxResults = 10): array
    {
        return $this->createQueryBuilder('s')
            ->select(['s.endcustomerName'])
            ->groupBy('s.endcustomerName')
            ->andWhere('LOWER(s.endcustomerName) LIKE LOWER(:query)')
            ->setParameter('query', '%' . $query . '%')
            ->setMaxResults($maxResults)
            ->getQuery()
            ->getSingleColumnResult();
    }

    /**
     * Find signings in given state that were expired by given number of days.
     *
     * @param int $days
     * @param \App\Enum\ESigningState $state
     * @param int $max Max results
     *
     * @return Signing Returns an array of Signing objects
     */
    public function findExpired(int $days, \App\Enum\ESigningState $state, int $max = 10): array
    {
        return $this->createQueryBuilder('s')
            ->join('s.signingState', 'ss')
            ->select('s, ss')
            ->andWhere('s.expirationDate <= :date')
            ->andWhere('ss.state = :state')
            ->setParameter('date', date('Y-m-d', time() - $days * 86400))
            ->setParameter('state', $state->value)
            ->orderBy('s.expirationDate', 'ASC')
            ->setMaxResults($max)
            ->getQuery()
            ->getResult();
    }

    /*
    public function findOneBySomeField($value): ?Signing
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
