<?php

namespace App\Repository;

use App\Entity\MarginForeign;
use App\Enum\EMarginType;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method MarginForeign|null find($id, $lockMode = null, $lockVersion = null)
 * @method MarginForeign|null findOneBy(array $criteria, array $orderBy = null)
 * @method MarginForeign[]    findAll()
 * @method MarginForeign[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 * @method MarginForeign[]    findByType(EMarginType $type, int $limit = null)
 */
class MarginForeignRepository extends AMarginRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MarginForeign::class);
    }
}
