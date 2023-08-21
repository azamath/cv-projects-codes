<?php

namespace App\Tests\DataFixtures;

use App\Factory\CompanyFactory;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class CompanyFixtures extends Fixture
{
    public const ADOBE = 'adobe';
    public const VMWARE = 'vmware';
    public const DIS1 = 'dis1';
    public const DIS2 = 'dis2';
    public const RES1 = 'res1';
    public const RES2 = 'res2';
    public const END1 = 'end1';
    public const END2 = 'end2';

    public function load(ObjectManager $manager): void
    {
        $this->addReference(
            self::ADOBE,
            CompanyFactory::new()->vendor()->create(['name' => 'Adobe', 'alias' => 'adobe'])->object(),
        );
        $this->addReference(
            self::VMWARE,
            CompanyFactory::new()->vendor()->create(['name' => 'VMWare', 'alias' => 'vmware'])->object(),
        );
        $this->addReference(
            self::DIS1,
            CompanyFactory::new()->distributor()->create(['name' => 'Distributor 1'])->object(),
        );
        $this->addReference(
            self::DIS2,
            CompanyFactory::new()->distributor()->create(['name' => 'Distributor 2', 'alias' => ''])->object(),
        );
        $this->addReference(
            self::RES1,
            CompanyFactory::new()->reseller()->create(['name' => 'Reseller 1'])->object(),
        );
        $this->addReference(
            self::RES2,
            CompanyFactory::new()->reseller()->create(['name' => 'Reseller 2'])->object(),
        );
        $this->addReference(
            self::END1,
            CompanyFactory::new()->endCustomer()->create()->object(),
        );
        $this->addReference(
            self::END2,
            CompanyFactory::new()->endCustomer()->create()->object(),
        );
    }
}
