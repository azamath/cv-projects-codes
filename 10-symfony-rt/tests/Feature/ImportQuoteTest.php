<?php

namespace App\Tests\Feature;

use App\Services\ImportService;
use App\Tests\DataFixtures\CompanyFixtures;
use App\Tests\DataFixtures\LicenseDistributorFixture;
use App\Tests\DataFixtures\UserFixtures;
use App\Tests\Traits\ContainerHelpers;
use App\Tests\Traits\HasVirtualFileSystem;
use App\Tests\Traits\LoadsFixtures;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Zenstruck\Foundry\Test\Factories;

class ImportQuoteTest extends WebTestCase
{
    use Factories;
    use ContainerHelpers;
    use LoadsFixtures;
    use HasVirtualFileSystem;

    private KernelBrowser $client;

    protected function setUp(): void
    {
        parent::setUp();
        $this->client = static::createClient([], ['HTTP_ACCEPT' => 'application/json']);
    }

    public function testUploadQuote(): void
    {
        $this->loadFixtures([
            CompanyFixtures::class,
            UserFixtures::class,
        ]);
        $user = $this->getEntityReference(UserFixtures::USER1);
        $vendor = $this->getEntityReference(CompanyFixtures::ADOBE);
        $reseller = $this->getEntityReference(CompanyFixtures::RES1);

        $this->mockImportService()->method('getPipelineId')->willReturn([
            'status' => 'success',
            'pipeline_id' => '123',
        ]);
        $this->mockImportService()->method('upload')->willReturn([
            'status' => 'success',
        ]);

        $this->client->loginUser($user);
        $this->client->request('POST', '/quotes/upload', [
            'vendorId' => $vendor->getCompanyId(),
            'resellerId' => $reseller->getCompanyId(),
        ], [
            'file' => $this->createUploadedFile(),
        ]);
        $this->assertResponseIsSuccessful();
    }

    public function testImportCallback()
    {
        $this->loadFixtures([
            CompanyFixtures::class,
            UserFixtures::class,
            LicenseDistributorFixture::class,
        ]);
        $user = $this->getEntityReference(UserFixtures::USER1);
        $vendor = $this->getEntityReference(CompanyFixtures::ADOBE);
        $reseller = $this->getEntityReference(CompanyFixtures::RES1);
        $fileName = 'test' . mt_rand() . '.csv';
        $importLogQuote = (new \App\Entity\ImportLogQuote())
            ->setFileName($fileName)
            ->setVendorId($vendor->getCompanyId())
            ->setResellerId($reseller->getCompanyId())
            ->setUserId($user->getUserId())
            ->setImportDate(new \DateTime())
            ->setImportResult(\App\Enum\EImportLogResult::PENDING);
        $this->getEntityManager()->persist($importLogQuote);
        $this->getEntityManager()->flush();

        $importLogId = $importLogQuote->getImportLogId();
        $this->client->jsonRequest('POST', "/quotes/import-callback/{$importLogId}", [
            'data' => [
                [
                    'quoteNumber' => 'QUOTE01',
                    'currencyCode' => 'EUR',
                    'endCustomer' => [
                        'name' => 'Some Name',
                    ],
                    'products' => [
                        [
                            'sku' => 'SKU1',
                            'name' => 'Product 1',
                            'price' => '100',
                            'quantity' => '2',
                        ]
                    ],
                ]
            ],
        ]);
        $this->assertResponseIsSuccessful();
        $responseJson = $this->client->getResponse()->getContent();
        $this->assertJson($responseJson);
        $responseData = json_decode($responseJson, true);
        $this->assertEmpty($responseData['errors'] ?? []);
        $this->assertEquals('success', $responseData['status']);

        $quotes = $this->getQuoteRepository()->findBy(['filename' => $fileName]);
        $this->assertCount(1, $quotes);
    }

    protected function mockImportService(): MockObject|ImportService
    {
        if (!isset($this->mockImportService)) {
            $this->mockImportService = $this->createMock(ImportService::class);
            self::getContainer()->set(ImportService::class, $this->mockImportService);
        }

        return $this->mockImportService;
    }

    protected function createUploadedFile(): UploadedFile
    {
        return new UploadedFile(
            $this->letsHaveVfsFile('tempfile'),
            'test.csv',
            'text/csv',
            null,
            true
        );
    }
}
