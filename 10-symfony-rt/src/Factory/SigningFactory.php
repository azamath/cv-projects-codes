<?php

namespace App\Factory;

use App\Entity\Signing;
use App\Entity\SigningState;
use App\Repository\SigningRepository;
use Zenstruck\Foundry\ModelFactory;
use Zenstruck\Foundry\Proxy;
use Zenstruck\Foundry\RepositoryProxy;

/**
 * @extends ModelFactory<Signing>
 *
 * @method static Signing|Proxy createOne(array $attributes = [])
 * @method static Signing[]|Proxy[] createMany(int $number, array|callable $attributes = [])
 * @method static Signing[]|Proxy[] createSequence(array|callable $sequence)
 * @method static Signing|Proxy find(object|array|mixed $criteria)
 * @method static Signing|Proxy findOrCreate(array $attributes)
 * @method static Signing|Proxy first(string $sortedField = 'id')
 * @method static Signing|Proxy last(string $sortedField = 'id')
 * @method static Signing|Proxy random(array $attributes = [])
 * @method static Signing|Proxy randomOrCreate(array $attributes = [])
 * @method static Signing[]|Proxy[] all()
 * @method static Signing[]|Proxy[] findBy(array $attributes)
 * @method static Signing[]|Proxy[] randomSet(int $number, array $attributes = [])
 * @method static Signing[]|Proxy[] randomRange(int $min, int $max, array $attributes = [])
 * @method static SigningRepository|RepositoryProxy repository()
 * @method Signing|Proxy create(array|callable $attributes = [])
 */
final class SigningFactory extends ModelFactory
{
    public function __construct()
    {
        parent::__construct();
    }

    public function forSomeQuote(): self
    {
        return $this->forQuote(
            QuoteFactory::new()->withSameCompanies()->withSameUser()->create()
        );
    }

    public function forQuote(\App\Entity\Quote|Proxy $quote): self
    {
        $quote = $quote instanceof Proxy ? $quote->object() : $quote;
        return $this->afterInstantiate(function (Signing $signing) use ($quote): void {
            $signingMeta = new \App\Entity\SigningMeta();
            $signingMeta->setQuote($quote);
            $signingMeta->setVendor($quote->getVendor());
            $signingMeta->setDistributor($quote->getOriginCompany());
            $signingMeta->setReseller($quote->getReseller());
            $signingMeta->setEndCustomer($quote->getEndCustomer());

            $signing
                ->setSigningMeta($signingMeta)
                ->setQuoteName($quote->getName())
                ->setQuoteNumber($quote->getQuoteNumber())
                ->setOriginQuoteNumber($quote->getOriginQuoteNumber())
                ->setVendorName($quote->getVendor()->getName())
                ->setDistributorName($quote->getOriginCompany()->getName())
                ->setResellerName($quote->getReseller()->getName())
                ->setResellerType($quote->getReseller()->getCompanyType()->value)
                ->setEndcustomerName($quote->getEndCustomer()->getName())
                ->setCreatedUser($quote->getCreatedUser());
        });
    }

    public function createdUser(\App\Entity\User|Proxy $user): self
    {
        return $this->addState([
            'createdUser' => $user,
        ]);
    }

    public function expiration(\DateTime|string $expiration, \DateTime|string $supportExpiration = null): self
    {
        $expiration = $this->createDateTime($expiration);
        $supportExpiration = $supportExpiration ? $this->createDateTime($supportExpiration) : $expiration;

        return $this->addState([
            'expirationDate' => $expiration,
            'supportExpirationDate' => $supportExpiration,
        ]);
    }

    public function signingState(\App\Enum\ESigningState $state): self
    {
        return $this->afterInstantiate(function (Signing $signing) use ($state) {
            $signing->getSigningState()->setState($state);
        });
    }

    protected function getDefaults(): array
    {
        $expiration = (new \DateTime('+1 month'))->setTime(0, 0);
        return [
            'createdDate' => self::faker()->dateTimeThisYear(),
            'createdUser' => UserFactory::new(),
            'expirationDate' => $expiration,
            'supportExpirationDate' => $expiration,
            'originCurrencyCode' => self::faker()->currencyCode(),
            'outputCurrencyCode' => self::faker()->currencyCode(),
        ];
    }

    protected function initialize(): self
    {
        // see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#initialization
        return $this
            ->afterInstantiate(function(Signing $signing): void {
                $signing->setSigningState(
                    self::factorySigningState($signing)->create()->object()
                );
            })
        ;
    }

    protected static function getClass(): string
    {
        return Signing::class;
    }

    protected static function factorySigningState(Signing $signing, $attributes = []): \Zenstruck\Foundry\AnonymousFactory
    {
        return \Zenstruck\Foundry\AnonymousFactory::new(SigningState::class, [
            'state' => \App\Enum\ESigningState::PENDING,
            'modifiedDate' => self::faker()->dateTimeThisMonth(),
            'modifiedUserId' => $signing->getCreatedUser()->getUserId(),
        ])
            ->withAttributes($attributes)
            ->withoutPersisting();
    }

    protected function createDateTime(\DateTime|string $expiration): \DateTime
    {
        return is_string($expiration) ? (new \DateTime($expiration))->setTime(0, 0) : $expiration;
    }
}
