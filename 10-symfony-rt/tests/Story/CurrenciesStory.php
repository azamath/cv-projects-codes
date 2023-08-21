<?php

namespace App\Tests\Story;

use App\Entity\Currency;
use App\Factory\UserFactory;
use Zenstruck\Foundry\AnonymousFactory;
use Zenstruck\Foundry\Factory;
use Zenstruck\Foundry\Proxy;
use Zenstruck\Foundry\Story;

final class CurrenciesStory extends Story
{
    public function build(): void
    {
        $user = UserFactory::createOne();
        $factory = AnonymousFactory::new(Currency::class, [
            'createdDate' => Factory::faker()->dateTimeThisYear(),
            'modifiedDate' => Factory::faker()->dateTimeThisYear(),
            'createdUserId' => $user->getUserId(),
            'modifiedUserId' => $user->getUserId(),
        ]);

        /** @var Currency[]|Proxy[] $currencies */
        $currencies = $factory
            ->sequence([
                [
                    'currencyCode' => 'EUR',
                    'conversionRate' => 1,
                    'currencySymbol' => 'EUR',
                ],
                [
                    'currencyCode' => 'SEK',
                    'conversionRate' => 10,
                    'currencySymbol' => 'SEK',
                ],
                [
                    'currencyCode' => 'USD',
                    'conversionRate' => 1.2,
                    'currencySymbol' => 'USD',
                ],
            ])
            ->create();

        foreach ($currencies as $currency) {
            $this->addState($currency->getCurrencyCode(), $currency);
        }
    }
}
