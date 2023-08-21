<?php
/**
 * Author: Azamat Holmeer
 */

namespace App\Api\DataProvider;

use ApiPlatform\Core\Bridge\Doctrine\Orm\Extension\QueryCollectionExtensionInterface;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Extension\QueryResultCollectionExtensionInterface;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Util\QueryNameGenerator;
use ApiPlatform\Core\DataProvider\ContextAwareCollectionDataProviderInterface;
use ApiPlatform\Core\DataProvider\RestrictedDataProviderInterface;
use App\Entity\Company;
use App\Repository\CompanyRepository;
use App\Security\User;
use App\Services\VendorsQueryService;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\Security\Core\Security;

class VendorsDataProvider implements
    ContextAwareCollectionDataProviderInterface,
    RestrictedDataProviderInterface,
    ExtensionsAwareDataProviderInterface
{
    protected const SUPPORTED_CLASS = Company::class;

    protected const SUPPORTED_OPERATION = 'vendors_import_quote';

    /**
     * @var QueryCollectionExtensionInterface[]
     */
    private iterable $collectionExtensions = [];

    public function __construct(
        private CompanyRepository $companyRepository,
        private VendorsQueryService $vendorsQueryService,
        private Security $security,
    )
    {
    }

    public function supports(string $resourceClass, string $operationName = null, array $context = []): bool
    {
        return self::SUPPORTED_CLASS === $resourceClass && self::SUPPORTED_OPERATION === $operationName;
    }

    /**
     * @inheritDoc
     * @return iterable
     */
    public function getCollection(string $resourceClass, string $operationName = null, array $context = [])
    {
        // ensure authenticated user is available
        /** @var User $user */
        $user = $this->security->getUser();
        if (null === $user) {
            throw new \RuntimeException('Can not proceed without authenticated user');
        }

        // get ImportConnections for user
        $importConnections = $this->vendorsQueryService->getQuoteImportConnections($user);

        // create main query
        $query = $this->companyRepository->createQueryBuilder('c');
        $this->vendorsQueryService->applyImportConnectionsFilter($query, $importConnections);

        // get results
        $companies = $this->getQueryResult($query, $resourceClass, $operationName, $context);

        return $this->vendorsQueryService->mapResultsToOutput($companies, $importConnections);
    }

    /**
     * Used in DI to provide API collection extension services. Configured in config/services.yaml.
     *
     * @param QueryCollectionExtensionInterface[] $collectionExtensions
     * @return static
     */
    public function setCollectionExtensions(iterable $collectionExtensions): static
    {
        $this->collectionExtensions = $collectionExtensions;

        return $this;
    }

    protected function getQueryResult(QueryBuilder $queryBuilder, string $resourceClass, ?string $operationName, array $context): mixed
    {
        $queryNameGenerator = new QueryNameGenerator();
        foreach ($this->collectionExtensions as $extension) {
            $extension->applyToCollection($queryBuilder, $queryNameGenerator, $resourceClass, $operationName, $context);

            if ($extension instanceof QueryResultCollectionExtensionInterface && $extension->supportsResult($resourceClass, $operationName, $context)) {
                return $extension->getResult($queryBuilder, $resourceClass, $operationName, $context);
            }
        }

        return $queryBuilder->getQuery()->getResult();
    }
}
