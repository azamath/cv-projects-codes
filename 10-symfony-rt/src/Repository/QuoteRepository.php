<?php

namespace App\Repository;

use App\Entity\Quote;
use App\Entity\Signing;
use App\Entity\SigningMeta;
use App\Entity\SigningState;
use App\Enum\ESigningState;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Quote|null find($id, $lockMode = null, $lockVersion = null)
 * @method Quote|null findOneBy(array $criteria, array $orderBy = null)
 * @method Quote[]    findAll()
 * @method Quote[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class QuoteRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Quote::class);
    }

    public function add(Quote $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function delete(Quote $entity, bool $flush = false): void
    {
        $entity->setDeleted(true);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Quote $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    // /**
    //  * @return Quote[] Returns an array of Quote objects
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

    /**
     * Finds Quotes pending for status update.
     *
     * @return Quote[]
     */
    public function findPendingForStateUpdate(): array
    {
        $qb = $this->createQueryBuilder('q');

        return $qb
            ->andWhere('q.baseSigningId IS NOT NULL')
            ->andWhere('q.resolvedState IS NOT NULL')
            ->andWhere('q.stateSynced = false')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Quote[]
     */
    public function findPendingForStateResolve(): array
    {
        $qb = $this->createQueryBuilder('q');

        return $qb
            ->distinct(true)
            ->innerJoin(SigningMeta::class, 'sm', Join::WITH, 'sm.quoteId = q.quoteId')
            ->innerJoin(Signing::class, 's', Join::WITH, 's.signingId = sm.signingId')
            ->innerJoin(SigningState::class, 'ss', Join::WITH, 'ss.signingId = sm.signingId')
            ->where('q.deleted = false')
            ->andWhere('s.deleted = false')
            ->andWhere('q.baseSigningId IS NOT NULL')
            ->andWhere(
                $qb->expr()->orX(
                    'q.stateSyncDate IS NULL',
                    'q.stateSyncDate < ss.modifiedDate',
                )
            )
            ->getQuery()
            ->getResult();
    }

    /**
     * @param Quote $quote
     * @return ESigningState[]
     * @throws \Doctrine\DBAL\Exception
     */
    public function fetchSigningStatesForQuote(Quote $quote): array
    {
        $sql = 'SELECT DISTINCT ss."state"
            FROM signings_state ss
            INNER JOIN signings s ON (s."signingId" = ss."signingId")
            INNER JOIN signings_meta sm ON (sm."signingId" = ss."signingId")
            WHERE s.deleted = FALSE AND sm."quoteId" = :quoteId
            ';

        $stmt = $this->getEntityManager()->getConnection()->prepare($sql);
        $result = $stmt->executeQuery(['quoteId' => $quote->getQuoteId()])->fetchFirstColumn();

        // return array of integers converted to array of enums
        return array_map(fn($val) => ESigningState::from($val), $result);
    }
}
