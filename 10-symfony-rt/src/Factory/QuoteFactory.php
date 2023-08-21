<?php

namespace App\Factory;

use App\Entity\Quote;
use App\Entity\QuoteCompany;
use App\Entity\QuoteExchangeRate;
use App\Entity\QuoteProduct;
use App\Enum\EMarginCalculation;
use App\Enum\EMarginValueType;
use App\Repository\QuoteRepository;
use Zenstruck\Foundry\AnonymousFactory;
use Zenstruck\Foundry\ModelFactory;
use Zenstruck\Foundry\Proxy;
use Zenstruck\Foundry\RepositoryProxy;

/**
 * @extends ModelFactory<Quote>
 *
 * @method static Quote|Proxy createOne(array $attributes = [])
 * @method static Quote[]|Proxy[] createMany(int $number, array|callable $attributes = [])
 * @method static Quote[]|Proxy[] createSequence(array|callable $sequence)
 * @method static Quote|Proxy find(object|array|mixed $criteria)
 * @method static Quote|Proxy findOrCreate(array $attributes)
 * @method static Quote|Proxy first(string $sortedField = 'id')
 * @method static Quote|Proxy last(string $sortedField = 'id')
 * @method static Quote|Proxy random(array $attributes = [])
 * @method static Quote|Proxy randomOrCreate(array $attributes = [])
 * @method static Quote[]|Proxy[] all()
 * @method static Quote[]|Proxy[] findBy(array $attributes)
 * @method static Quote[]|Proxy[] randomSet(int $number, array $attributes = [])
 * @method static Quote[]|Proxy[] randomRange(int $min, int $max, array $attributes = [])
 * @method static QuoteRepository|RepositoryProxy repository()
 * @method Quote|Proxy create(array|callable $attributes = [])
 */
final class QuoteFactory extends ModelFactory
{
    public function __construct()
    {
        parent::__construct();
    }

    protected function getDefaults(): array
    {
        $number = self::faker()->bothify('Q#####');
        $expiration = (new \DateTime('+1 month'))->setTime(0, 0);

        return [
            'quoteNumber' => $number,
            'originQuoteNumber' => $number,
            'simpleQuoteNumber' => $number,
            'fileName' => strtolower($number) . '.xlsx',
            'name' => sprintf("Quote %s", $number),
            'productCnt' => self::faker()->numberBetween(1, 20),
            'quoteDuration' => self::faker()->numberBetween(30, 300),
            'originCurrencyCode' => self::faker()->currencyCode(),
            'vendor' => CompanyFactory::new()->vendor(),
            'vendorName' => self::faker()->company(),
            'reseller' => CompanyFactory::new()->reseller(),
            'originCompany' => CompanyFactory::new()->distributor(),
            'endCustomer' => CompanyFactory::new()->endCustomer(),
            'expirationDate' => $expiration,
            'supportExpirationDate' => $expiration,
            'createdDate' => self::faker()->dateTimeThisYear(),
            'modifiedDate' => self::faker()->dateTimeThisYear(),
            'confirmed' => true,
        ];
    }

    public function withSameCompanies(): self
    {
        return $this->addState([
            'vendor' => CompanyFactory::new()->vendor()->create(),
            'originCompany' => CompanyFactory::new()->distributor()->create(),
            'reseller' => CompanyFactory::new()->reseller()->create(),
            'endCustomer' => CompanyFactory::new()->endCustomer()->create(),
        ]);
    }

    public function withSameUser(): self
    {
        return $this->user(UserFactory::createOne());
    }

    public function user(\App\Entity\User|Proxy $user): self
    {
        return $this->addState([
            'createdUser' => $user,
            'modifiedUser' => $user,
        ]);
    }

    public function withBaseSinging($baseSigningId = null): self
    {
        return $this->addState([
            'baseSigningId' => $baseSigningId ?? self::faker()->randomNumber(6),
        ]);
    }

    public function full(): self
    {
        return $this->afterInstantiate(function (Quote $quote) {
            // add some products
            /** @var QuoteProduct[]|Proxy[] $products */
            $products = self::quoteProduct()->many(self::faker()->numberBetween(1, 5))->create();
            foreach ($products as $product) {
                $quote->addQuoteProduct($product->object());
            }

            // add some exchange rates
            /** @var QuoteExchangeRate[]|Proxy[] $exchangeRates */
            $exchangeRates = self::quoteExchangeRate()
                ->sequence([['currencyCode' => 'EUR'], ['currencyCode' => 'SEK'], ['currencyCode' => 'USD']])
                ->create();
            foreach ($exchangeRates as $exchangeRate) {
                $quote->addExchangeRate($exchangeRate->object());
            }

            // set some company
            $quote->setQuoteCompany(
               self::quoteCompany()->create()->object()
            );
        });
    }

    protected function initialize(): self
    {
        // see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#initialization
        return $this
            ->afterInstantiate(function (Quote $quote): void {
                $user = $quote->getCreatedUser();
                if (!$user) {
                    $user = UserFactory::createOne()->object();
                    $quote->setCreatedUser($user);
                }
                if (!$quote->getModifiedUser()) {
                    $quote->setModifiedUser($user);
                }
            });
    }

    protected static function getClass(): string
    {
        return Quote::class;
    }

    public static function quoteProduct(): AnonymousFactory
    {
        return AnonymousFactory::new(QuoteProduct::class, [
            'sku' => self::faker()->bothify('???###'),
            'name' => self::faker()->word(),
            'description' => self::faker()->words(3, true),
            'productGroup' => '',
            'quantity' => self::faker()->numberBetween(1, 10),
            'price' => self::faker()->numberBetween(10, 100),
            'singlePrice' => self::faker()->numberBetween(10, 100),
            'marginSelfValue' => 0,
            'marginSelfValueType' => EMarginValueType::PERCENTAGE,
            'marginSelfCalculationType' => EMarginCalculation::MARGIN,
        ])
            ->withoutPersisting();
    }

    public static function quoteExchangeRate(): AnonymousFactory
    {
        return AnonymousFactory::new(QuoteExchangeRate::class, [
            'currencyCode' => self::faker()->currencyCode(),
            'conversionRate' => self::faker()->randomFloat(4, 0.1, 300),
        ])
            ->withoutPersisting();
    }

    public static function quoteCompany(): AnonymousFactory
    {
        return AnonymousFactory::new(QuoteCompany::class, [
            'invoiceCustomerReference' => self::faker()->name(),
            'invoiceEmailAddress' => self::faker()->email(),
            'invoicePhoneNo' => self::faker()->phoneNumber(),
            'invoiceAddress' => self::faker()->address(),
            'invoiceZip' => self::faker()->postcode(),
            'invoiceCity' => self::faker()->city(),
        ])
            ->withoutPersisting();
    }
}
