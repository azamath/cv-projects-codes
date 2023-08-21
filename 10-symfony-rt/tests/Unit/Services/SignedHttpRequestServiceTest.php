<?php

namespace App\Tests\Unit\Services;

use App\Entity\Company;
use App\Repository\CompanyRepository;
use App\Services\CryptoService;
use App\Services\SignatureValidatorService;
use App\Services\SignedHttpRequestService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Contracts\HttpClient\Exception\HttpExceptionInterface;

class SignedHttpRequestServiceTest extends TestCase
{

    public function testRequestIsCorrect()
    {
        $companyRemoteId = $this->getMockCompanySelf()->getRemoteCompanyId();
        $signature = 'test signature';
        $url = 'https://distributor.loc/';
        $method = 'PUT';
        $response = ['message' => 'Success'];
        $mockResponse = new MockResponse(json_encode($response), ['http_code' => 200]);
        $client = new MockHttpClient($mockResponse);
        $service = new SignedHttpRequestService(
            $client,
            $this->getMockCompanyRepository(),
            $this->getMockCryptoService($signature),
        );

        $result = $service->fetch($method, $url, ['state' => 1]);

        $this->assertEquals($response, $result);
        $this->assertSame($method, $mockResponse->getRequestMethod());
        $this->assertSame($url, $mockResponse->getRequestUrl());
        $this->assertContains(
            'Content-Type: application/json',
            $mockResponse->getRequestOptions()['headers']
        );
        $this->assertContains(
            SignatureValidatorService::SENDER_HEADER . ': ' . $companyRemoteId,
            $mockResponse->getRequestOptions()['headers']
        );
        $this->assertContains(
            SignatureValidatorService::SIGNATURE_HEADER . ': ' . $signature,
            $mockResponse->getRequestOptions()['headers']
        );
    }

    /**
     * @param $httpCode
     * @dataProvider exceptionsDataProvider
     */
    public function testExceptions($httpCode)
    {
        $mockResponse = new MockResponse('error', ['http_code' => $httpCode]);
        $client = new MockHttpClient($mockResponse);
        $service = new SignedHttpRequestService(
            $client,
            $this->getMockCompanyRepository(),
            $this->getMockCryptoService(),
        );

        $this->expectException(HttpExceptionInterface::class);
        $service->fetch('POST', 'http://distributor.loc', 'test data');
    }

    public function exceptionsDataProvider(): array
    {
        return [[400], [401], [402], [403], [404], [500], [501], [502], [503]];
    }

    /**
     * @return CompanyRepository|mixed|\PHPUnit\Framework\MockObject\MockObject
     */
    protected function getMockCompanyRepository(): mixed
    {
        $mock = $this->createMock(CompanyRepository::class);
        $mock->expects($this->any())
            ->method('findCompanySelf')
            ->willReturn(
                $this->getMockCompanySelf()
            );

        return $mock;
    }

    /**
     * @return Company
     */
    protected function getMockCompanySelf(): Company
    {
        return (new Company())
            ->setRemoteCompanyId(2);
    }

    /**
     * @return CryptoService|mixed|\PHPUnit\Framework\MockObject\MockObject
     */
    protected function getMockCryptoService(string $mockSignature = 'test signature'): mixed
    {
        $mock = $this->createMock(CryptoService::class);
        $mock->expects($this->any())
            ->method('createSignature')
            ->willReturn($mockSignature);

        return $mock;
    }
}
