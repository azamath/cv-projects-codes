<?php

namespace App\Tests\DataFixtures;

use App\Factory\UserFactory;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class UserFixtures extends Fixture
{
    public const ADMIN = 'user_admin';
    public const USER1 = 'user1';

    public function load(ObjectManager $manager): void
    {
        $this->addReference(
            self::ADMIN,
            UserFactory::new()->admin()->create()->object(),
        );
        $this->addReference(
            self::USER1,
            UserFactory::new()->create()->object(),
        );
    }
}
