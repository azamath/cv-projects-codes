<?php

namespace App\Tests\Story;

use App\Enum\ESigningState;
use App\Factory\QuoteFactory;
use App\Factory\SigningFactory;
use App\Factory\UserFactory;
use Zenstruck\Foundry\Story;

final class ExpiredSigningsStory extends Story
{
    public function build(): void
    {
        $user = UserFactory::createOne();
        $quote = QuoteFactory::new()->user($user)->create()->object();

        // SystemInfoFixture sets 120 days
        $dates = ['-119 days', '-120 days', '-121 days'];
        foreach ($dates as $date) {
            // pending signing
            SigningFactory::new()
                ->createdUser($user)
                ->forQuote($quote)
                ->expiration($date)
                ->signingState(ESigningState::PENDING)
                ->create();
            // processed signing
            SigningFactory::new()
                ->createdUser($user)
                ->forQuote($quote)
                ->expiration($date)
                ->signingState(ESigningState::SOLD)
                ->create();
        }
    }
}
