<?php

namespace App\Tests\Unit\Services;

use App\Services\SystemInfoService;
use App\Tests\DataFixtures\SystemInfoFixtures;
use App\Tests\Traits\LoadsFixtures;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class SystemInfoServiceTest extends KernelTestCase
{
    use LoadsFixtures;

    public function testGetInfo(): void
    {
        self::bootKernel();
        $this->loadFixtures([
            SystemInfoFixtures::class,
        ]);
        // values based on fixtures
        $this->assertSame('EUR', $this->getService()->get(SystemInfoService::BASE_CURRENCY_CODE));
        $this->assertSame('120', $this->getService()->get(SystemInfoService::SIGNING_INACTIVATE_EXPIRATION_DAYS));
    }

    public function testSetInfo(): void
    {
        self::bootKernel();
        $this->loadFixtures([
            SystemInfoFixtures::class,
        ]);
        $this->getService()->set(SystemInfoService::BASE_CURRENCY_CODE, 'SEK');
        $this->getService()->set(SystemInfoService::SIGNING_INACTIVATE_EXPIRATION_DAYS, null);
        $this->getService()->set('some_new_key', 'some value');
        $this->assertSame('SEK', $this->getService()->get(SystemInfoService::BASE_CURRENCY_CODE));
        $this->assertNull($this->getService()->get(SystemInfoService::SIGNING_INACTIVATE_EXPIRATION_DAYS));
        $this->assertSame('some value', $this->getService()->get('some_new_key'));
    }

    protected function getService(): SystemInfoService
    {
        return self::getContainer()->get(SystemInfoService::class);
    }
}
