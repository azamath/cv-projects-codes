<?php
/**
 * Author: Azamat Holmeer
 */

namespace App\Tests\DataFixtures;

use App\Entity\Info;
use App\Services\SystemInfoService;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class SystemInfoFixtures extends Fixture
{

    public function load(ObjectManager $manager)
    {
        $entries = [
            SystemInfoService::BASE_CURRENCY_CODE => 'EUR',
            SystemInfoService::SIGNING_INACTIVATE_EXPIRATION_DAYS => 120,
        ];

        foreach ($entries as $key => $value) {
            $info = (new Info())
                ->setKey($key)
                ->setValue((string)$value);
            $manager->persist($info);
        }

        $manager->flush();
    }
}
