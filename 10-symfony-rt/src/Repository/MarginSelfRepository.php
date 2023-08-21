<?php

namespace App\Repository;

use App\Entity\MarginSelf;
use App\Enum\EMarginType;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method MarginSelf|null find($id, $lockMode = null, $lockVersion = null)
 * @method MarginSelf|null findOneBy(array $criteria, array $orderBy = null)
 * @method MarginSelf[]    findAll()
 * @method MarginSelf[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 * @method MarginSelf[]    findByType(EMarginType $type, int $limit = null)
 */
class MarginSelfRepository extends AMarginRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MarginSelf::class);
    }
}
