<?php
/**
 * Author: Azamat Holmeer
 */

namespace App\Tests\Traits;

use App\Repository\CompanyRepository;
use App\Repository\QuoteRepository;
use App\Repository\SigningRepository;
use App\Repository\SigningStateRepository;
use App\Repository\UserRepository;
use App\Services\SystemInfoService;
use Doctrine\ORM\EntityManagerInterface;

trait ContainerHelpers
{

    protected function getEntityManager(): EntityManagerInterface
    {
        return self::getContainer()->get(EntityManagerInterface::class);
    }

    protected function getCompanyRepository(): CompanyRepository
    {
        return self::getContainer()->get(CompanyRepository::class);
    }

    protected function getQuoteRepository(): QuoteRepository
    {
        return self::getContainer()->get(QuoteRepository::class);
    }

    protected function getSigningRepository(): SigningRepository
    {
        return self::getContainer()->get(SigningRepository::class);
    }

    protected function getSigningStateRepository(): SigningStateRepository
    {
        return self::getContainer()->get(SigningStateRepository::class);
    }

    protected function getSystemInfoService(): SystemInfoService
    {
        return self::getContainer()->get(SystemInfoService::class);
    }

    protected function getUserRepository(): UserRepository
    {
        return self::getContainer()->get(UserRepository::class);
    }
}
