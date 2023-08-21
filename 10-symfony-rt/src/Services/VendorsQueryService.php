<?php
/**
 * Author: Azamat Holmeer
 */

namespace App\Services;

use App\Dto\Output\Vendor;
use App\Entity\Company;
use App\Entity\ImportConnection;
use App\Enum\ECompanyType;
use App\Repository\ImportConnectionRepository;
use App\Repository\PermissionsAggregatedDataRepository;
use App\Security\User;
use Doctrine\ORM\QueryBuilder;

class VendorsQueryService
{
    public function __construct(
        private ImportConnectionRepository $importConnectionRepository,
        private PermissionsAggregatedDataRepository $permissionsAggregatedDataRepository,
    )
    {
    }

    /**
     * @return iterable|ImportConnection[]
     */
    public function getQuoteImportConnections(User $user): iterable
    {
        // get import connections for a user
        $icQuery = $this->importConnectionRepository->createQueryBuilder('ic');
        $icQuery
            ->andWhere('ic.enabledQuoteImport = true')
            ->andWhere(
                $icQuery->expr()->in(
                    'ic.vendorId',
                    $this->permissionsAggregatedDataRepository->createQueryBuilder('pad')
                        ->select('pad.vendorId')
                        ->andWhere('pad.userId = :authUserId')
                        ->andWhere('pad.permissionCode like :permissionCode')
                        ->getDQL()
                )
            )
            ->setParameter('authUserId', $user->getUserId())
            ->setParameter('permissionCode', 'VENDOR_%_QUOTE_IMPORT');

        return $icQuery->getQuery()->getResult();
    }

    /**
     * @param QueryBuilder $query
     * @param ImportConnection[] $importConnections
     * @return void
     */
    public function applyImportConnectionsFilter(QueryBuilder $query, iterable $importConnections)
    {
        $query->andWhere('c.companyType = :companyType');
        $query->setParameter('companyType', ECompanyType::VENDOR->value);

        $vendorIds = [];
        foreach ($importConnections as $importConnection) {
            $vendorIds[] = $importConnection->getVendorId();
        }
        $query->andWhere($query->expr()->in('c.companyId', ':vendorIds'));
        $query->setParameter(':vendorIds', $vendorIds);
    }

    /**
     * @param Company[] $companies
     * @param ImportConnection[] $importConnections
     * @return Vendor[]
     */
    public function mapResultsToOutput(iterable $companies, iterable $importConnections): iterable
    {
        /** @var ImportConnection[] $mappedByVendor */
        $mappedByVendor = [];
        foreach ($importConnections as $importConnection) {
            $mappedByVendor[$importConnection->getVendorId()] = $importConnection;
        }

        $result = [];
        foreach ($companies as $company) {
            $output = new Vendor();
            $output->vendorId = $company->getCompanyId();
            $output->name = $company->getName();
            $output->alias = $company->getAlias();
            $output->quotesMethod = $mappedByVendor[$output->vendorId]->getQuotesMethod();
            $result[] = $output;
        }

        return $result;
    }
}
