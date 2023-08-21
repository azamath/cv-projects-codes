<?php

namespace App\Repository;

use App\Entity\Company;
use App\Entity\License;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Company|null find($id, $lockMode = null, $lockVersion = null)
 * @method Company|null findOneBy(array $criteria, array $orderBy = null)
 * @method Company[]    findAll()
 * @method Company[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CompanyRepository extends ServiceEntityRepository
{
    private ManagerRegistry $registry;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Company::class);
        $this->registry = $registry;
    }

//    /**
//     * @return Company[] Returns an array of Company objects
//     */
//    public function findByRemoteId($value)
//    {
//        return $this->createQueryBuilder('c')
//            ->andWhere('c.remoteCompanyId = :val')
//            ->setParameter('val', $value)
//            ->orderBy('c.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult();
//    }

    public function findOneByRemoteId($value): ?Company
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.remoteCompanyId = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findCompanySelf(): Company
    {
        /** @var LicenseRepository $licenseRepository */
        $licenseRepository = $this->registry->getRepository(License::class);
        $licenseCompanyId = $licenseRepository->findOneBy([], ['licenseId' => 'ASC'])->getCompanyId();

        $company = $this->find($licenseCompanyId);
        if (!$company) {
            throw new \RuntimeException(
                sprintf(
                    'Could not resolve company self "%s"',
                    $licenseCompanyId
                )
            );
        }

        return $company;
    }

}
