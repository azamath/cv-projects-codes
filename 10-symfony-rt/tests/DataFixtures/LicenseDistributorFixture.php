<?php

namespace App\Tests\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class LicenseDistributorFixture extends Fixture implements \Doctrine\Common\DataFixtures\DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        /** @var \App\Entity\Company $dist */
        $dist = $this->getReference(CompanyFixtures::DIS1);

        $license = (new \App\Entity\License())
            ->setLicenseBegin(new \DateTime())
            ->setLicenseExpire(new \DateTime('+1 year'))
            ->setLicenseKey('DMT1234567890')
            ->setCompany($dist)
            ->setCustomerId(1);
        $manager->persist($license);
        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            CompanyFixtures::class,
        ];
    }
}
