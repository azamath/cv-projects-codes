<?php

namespace App\Factory;

use App\Entity\Company;
use App\Repository\CompanyRepository;
use Zenstruck\Foundry\ModelFactory;
use Zenstruck\Foundry\Proxy;
use Zenstruck\Foundry\RepositoryProxy;

/**
 * @extends ModelFactory<Company>
 *
 * @method static Company|Proxy createOne(array $attributes = [])
 * @method static Company[]|Proxy[] createMany(int $number, array|callable $attributes = [])
 * @method static Company[]|Proxy[] createSequence(array|callable $sequence)
 * @method static Company|Proxy find(object|array|mixed $criteria)
 * @method static Company|Proxy findOrCreate(array $attributes)
 * @method static Company|Proxy first(string $sortedField = 'id')
 * @method static Company|Proxy last(string $sortedField = 'id')
 * @method static Company|Proxy random(array $attributes = [])
 * @method static Company|Proxy randomOrCreate(array $attributes = [])
 * @method static Company[]|Proxy[] all()
 * @method static Company[]|Proxy[] findBy(array $attributes)
 * @method static Company[]|Proxy[] randomSet(int $number, array $attributes = [])
 * @method static Company[]|Proxy[] randomRange(int $min, int $max, array $attributes = [])
 * @method static CompanyRepository|RepositoryProxy repository()
 * @method Company|Proxy create(array|callable $attributes = [])
 */
final class CompanyFactory extends ModelFactory
{
    public function __construct()
    {
        parent::__construct();
    }

    public function vendor(): self
    {
        return $this->addState([
            'companyType' => \App\Enum\ECompanyType::VENDOR,
        ]);
    }

    public function distributor(): self
    {
        return $this->addState([
            'companyType' => \App\Enum\ECompanyType::DISTRIBUTOR,
        ]);
    }

    public function reseller(): self
    {
        return $this->addState([
            'companyType' => \App\Enum\ECompanyType::RESELLER,
        ]);
    }

    public function virtualReseller(): self
    {
        return $this->addState([
            'companyType' => \App\Enum\ECompanyType::VIRTUAL_RESELLER,
        ]);
    }

    public function endCustomer(): self
    {
        return $this->addState([
            'companyType' => \App\Enum\ECompanyType::END_CUSTOMER,
        ]);
    }

    public function selfCompany(): self
    {
        return $this->addState([
            'companyType' => \App\Enum\ECompanyType::SELF,
        ]);
    }

    protected function getDefaults(): array
    {
        return [
            'remoteCompanyId' => self::faker()->randomNumber(),
            'name' => self::faker()->company(),
            'alias' => self::faker()->regexify('[a-z\_]{6,9}'),
            'companyType' => \App\Enum\ECompanyType::END_CUSTOMER,
        ];
    }

    protected function initialize(): self
    {
        // see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#initialization
        return $this
            // ->afterInstantiate(function(Company $company): void {})
        ;
    }

    protected static function getClass(): string
    {
        return Company::class;
    }
}
