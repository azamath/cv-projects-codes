<?php
/**
 * Author: Azamat Holmeer
 */

namespace App\Tests\Feature;

use App\Enum\EAppContext;
use App\Enum\ESigningState;
use App\Handler\ProcessExpiredSigningsHandler;
use App\Services\SystemInfoService;
use App\Tests\Story\ExpiredSigningsStory;
use App\Tests\Traits\ContainerHelpers;
use App\Tests\Traits\LoadsFixtures;
use DateTime;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Test\Factories;

class InactivateExpiredSigningsFunctionalTest extends KernelTestCase
{
    use Factories;
    use ContainerHelpers;
    use LoadsFixtures;

    protected function setUp(): void
    {
        parent::setUp();
        self::bootKernel();
    }

    public function testExpiredSigningsNotProcessedOnDistributor()
    {
        ExpiredSigningsStory::load();
        $this->getSystemInfoService()->set(SystemInfoService::SIGNING_INACTIVATE_EXPIRATION_DAYS, 120);
        $handler = $this->getHandler(EAppContext::DISTRIBUTOR->value);
        $processedCount = $handler->handle();
        $this->assertNull($processedCount);
    }

    public function testExpiredSigningsNotProcessedWithoutSystemSetting()
    {
        ExpiredSigningsStory::load();
        // no setting
        $this->getSystemInfoService()->set(SystemInfoService::SIGNING_INACTIVATE_EXPIRATION_DAYS, null);
        $handler = $this->getHandler(EAppContext::RESELLER->value);
        $processedCount = $handler->handle();
        $this->assertNull($processedCount);

        // empty string
        $this->getSystemInfoService()->set(SystemInfoService::SIGNING_INACTIVATE_EXPIRATION_DAYS, '');
        $handler = $this->getHandler(EAppContext::RESELLER->value);
        $processedCount = $handler->handle();
        $this->assertNull($processedCount);
    }

    public function testNoExpiredSignings()
    {
        $this->getSystemInfoService()->set(SystemInfoService::SIGNING_INACTIVATE_EXPIRATION_DAYS, 120);
        $handler = $this->getHandler(EAppContext::RESELLER->value);
        $processedCount = $handler->handle();
        $this->assertSame(0, $processedCount);
    }

    public function testExpiredSigningsProcessed()
    {
        ExpiredSigningsStory::load();
        $this->getSystemInfoService()->set(SystemInfoService::SIGNING_INACTIVATE_EXPIRATION_DAYS, 120);

        // remember signing IDs for inactivation
        $signingsToInactivate = new \Doctrine\Common\Collections\ArrayCollection();
        $signings = [];
        foreach ($this->getSigningRepository()->findAll() as $signing) {
            $signings[$signing->getSigningId()] = $signing;
            if ($this->shouldInactivateSigning($signing)) {
                $signingsToInactivate[] = $signing->getSigningId();
            }
        }
        $this->getEntityManager()->clear();

        $handler = $this->getHandler(EAppContext::RESELLER->value);

        // fixture creates 2 signings matching condition
        $processedCount = $handler->handle();
        $this->assertSame(2, $processedCount);

        // Clear entity cache to get fresh results
        $this->getEntityManager()->clear();
        foreach ($this->getSigningRepository()->findAll() as $signing) {
            if ($signingsToInactivate->contains($signing->getSigningId())) {
                $this->assertSame(
                    ESigningState::INACTIVATED,
                    $signing->getSigningState()->getState(),
                );
            }
            else {
                $this->assertSame(
                    $signings[$signing->getSigningId()]->getSigningState()->getState(),
                    $signing->getSigningState()->getState(),
                );
            }
        }

        // second time there should be 0
        $processedCount = $handler->handle();
        $this->assertSame(0, $processedCount);
    }

    protected function getHandler($appContext): ProcessExpiredSigningsHandler
    {
        return new ProcessExpiredSigningsHandler(
            $appContext,
            $this->getSystemInfoService(),
            $this->getSigningRepository(),
            $this->getSigningStateRepository(),
        );
    }

    protected function shouldInactivateSigning(\App\Entity\Signing $signing): bool
    {
        return $signing->getExpirationDate()->diff(new DateTime())->days >= 120 && ESigningState::PENDING === $signing->getSigningState()->getState();
    }
}
