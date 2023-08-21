<?php
/**
 * Author: Azamat Holmeer
 */

namespace App\Tests\Unit\Handler\ImportQuote;

use App\Entity\Company;
use App\Entity\ImportLogQuote;
use App\Handler\ImportQuote\UploadQuoteHandler;
use App\Repository\CompanyRepository;
use App\Services\ImportService;
use App\Services\UploadsService;
use App\Services\UploadsServiceInterface;
use App\Tests\Traits\HasVirtualFileSystem;
use App\Tests\Traits\MocksDoctrine;
use App\Tests\Traits\MocksSecurity;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class UploadQuoteHandlerTest extends TestCase
{
    use MocksDoctrine;
    use MocksSecurity;
    use HasVirtualFileSystem;

    public function testHandleSuccess()
    {
        // setup conditions
        $this->letsHaveAuthUser();

        // setup mocks
        $this->getMockImportService()
            ->expects($this->once())
            ->method('getPipelineId')->with('csv', 'vendorAlias');
        $this->getMockImportService()
            ->expects($this->once())
            ->method('upload')->with($this->anything(), '123');

        // call handler
        $res = $this->createHandler()->handle($this->createUploadedFile(), 1, 2);

        // assertions
        $this->assertEquals('vendorAlias', $res['vendorCode']);
        $this->assertEquals('csv', $res['fileType']);
        $importLogQuote = $res['importLogQuote'];
        $this->assertInstanceOf(ImportLogQuote::class, $importLogQuote);
        $this->assertEquals(1, $importLogQuote->getVendorId());
        $this->assertEquals(2, $importLogQuote->getResellerId());
    }

    protected function createHandler(): UploadQuoteHandler
    {
        return new UploadQuoteHandler(
            $this->getMockCompanyRepository(),
            $this->getMockUploadService(),
            $this->getMockImportService(),
            $this->getMockSecurity(),
            $this->getMockUrlGeneratorService(),
            $this->getMockDoctrine(),
        );
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

    protected function createVendor(): Company
    {
        return (new Company())
            ->setCompanyId(1)
            ->setAlias('vendorAlias');
    }

    protected function getMockCompanyRepository(): MockObject|CompanyRepository
    {
        if (!isset($this->companyRepository)) {
            $this->companyRepository = $this->createMock(CompanyRepository::class);
            $this->companyRepository->method('find')->willReturn($this->createVendor());
        }
        return $this->companyRepository;
    }

    protected function getMockUploadService(): MockObject|UploadsServiceInterface
    {
        if (!isset($this->uploadsService)) {
            $this->uploadsService = $this->createMock(UploadsService::class);
            $this->uploadsService->method('moveUploadedFile')->willReturn(
                new File($this->letsHaveVfsFile('uploads/test.csv'))
            );
        }

        return $this->uploadsService;
    }

    protected function getMockImportService(): ImportService|MockObject
    {
        if (!isset($this->importService)) {
            $this->importService = $this->createMock(ImportService::class);
            $this->importService->method('getPipelineId')->willReturn(
                [
                    'status' => 'success',
                    'pipeline_id' => '123',
                ]
            );
        }

        return $this->importService;
    }

    protected function getMockUrlGeneratorService(): MockObject|UrlGeneratorInterface
    {
        if (!isset($this->urlGeneratorService)) {
            $this->urlGeneratorService = $this->createMock(UrlGeneratorInterface::class);
            $this->urlGeneratorService->method('generate')->willReturn('https://example.com/callback');
        }

        return $this->urlGeneratorService;
    }
}
