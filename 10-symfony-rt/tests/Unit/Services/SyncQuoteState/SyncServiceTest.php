<?php

namespace App\Tests\Unit\Services\SyncQuoteState;

use App\Entity\Quote;
use App\Entity\System;
use App\Enum\ESigningState;
use App\Repository\SystemRepository;
use App\Services\SignedHttpRequestService;
use App\Services\SyncQuoteState\SyncService;
use App\Tests\Traits\MocksDoctrine;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\Exception\ClientException;
use Symfony\Component\HttpClient\Exception\JsonException;
use Symfony\Component\HttpClient\Exception\ServerException;
use Symfony\Component\HttpClient\Exception\TimeoutException;
use Symfony\Component\HttpClient\Exception\TransportException;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;

class SyncServiceTest extends TestCase
{
    use MocksDoctrine;

    public function testSyncCorrectParams()
    {
        $mockSystemRepository = $this->getMockSystemRepository();
        $mockHttpRequest = $this->createMock(SignedHttpRequestService::class);

        $quote = $this->createTestQuote();

        $service = $this->getSyncService(mockSystemRepository: $mockSystemRepository, mockHttpRequest: $mockHttpRequest);

        $mockHttpRequest->expects($this->once())
            ->method('fetch')
            ->with(
                $this->equalTo('PUT'),
                $this->equalTo('http://distributor.loc:80/app2/internal/signings/99/state'),
                $this->equalTo(['state' => 1]),
            );

        $service->sync($quote);
    }

    public function testSyncSuccess()
    {
        $mockHttpRequest = $this->createMock(SignedHttpRequestService::class);

        $quote = $this->createTestQuote();
        $this->assertNull($quote->getStateSyncDate());
        $this->assertFalse($quote->getStateSynced());

        $service = $this->getSyncService(mockHttpRequest: $mockHttpRequest);

        $mockHttpRequest->expects($this->once())
            ->method('fetch')
            ->willReturn(['message' => 'success']);

        $result = $service->sync($quote);

        $this->assertTrue($result);
        $this->assertNotNull($quote->getStateSyncDate());
        $this->assertNotNull($quote->getStateSyncTryDate());
        $this->assertTrue($quote->getStateSynced());
        $this->assertEquals(1, $quote->getStateSyncTries());
    }

    /**
     * @param ExceptionInterface $exception
     * @dataProvider syncFailExceptionsProvider
     */
    public function testSyncFailed(ExceptionInterface $exception)
    {
        $mockHttpRequest = $this->createMock(SignedHttpRequestService::class);

        $quote = $this->createTestQuote();
        $tries = $quote->getStateSyncTries();
        $this->assertNull($quote->getStateSyncDate());
        $this->assertFalse($quote->getStateSynced());

        $service = $this->getSyncService(mockHttpRequest: $mockHttpRequest);

        $mockHttpRequest->expects($this->once())
            ->method('fetch')
            ->willThrowException($exception);

        $result = $service->sync($quote);

        $this->assertFalse($result);
        $this->assertNull($quote->getStateSyncDate());
        $this->assertNotNull($quote->getStateSyncTryDate());
        $this->assertFalse($quote->getStateSynced());
        $this->assertEquals($tries + 1, $quote->getStateSyncTries());
    }

    public function syncFailExceptionsProvider(): array
    {
        return [
            [new ClientException(new MockResponse('bad request', ['http_code' => 400]))],
            [new ClientException(new MockResponse('unauthorized', ['http_code' => 401]))],
            [new ClientException(new MockResponse('forbidden', ['http_code' => 403]))],
            [new ClientException(new MockResponse('not found', ['http_code' => 404]))],
            [new ServerException(new MockResponse('server', ['http_code' => 500]))],
            [new ServerException(new MockResponse('bad gateway', ['http_code' => 502]))],
            [new ServerException(new MockResponse('unavailable', ['http_code' => 503]))],
            [new ServerException(new MockResponse('timeout', ['http_code' => 504]))],
            [new JsonException()],
            [new TransportException()],
            [new TimeoutException()],
        ];
    }

    /**
     * @param mixed|null $mockDoctrine
     * @param mixed|null $mockSystemRepository
     * @param mixed $mockHttpRequest
     * @return SyncService
     */
    protected function getSyncService(mixed $mockDoctrine = null, mixed $mockSystemRepository = null, mixed $mockHttpRequest = null): SyncService
    {
        return new SyncService(
            $mockDoctrine ?? $this->getMockDoctrine(),
            $mockSystemRepository ?? $this->getMockSystemRepository(),
            $mockHttpRequest,
            '/app2',
        );
    }

    /**
     * @return SystemRepository|MockObject
     */
    protected function getMockSystemRepository(): SystemRepository|MockObject
    {
        $mock = $this->createMock(SystemRepository::class);

        $system = (new System())
            ->setHost('http://distributor.loc')
            ->setPort(80);

        $mock->expects($this->any())
            ->method('findOneByCompanyId')
            ->willReturn($system);

        return $mock;
    }

    /**
     * @return Quote
     */
    protected function createTestQuote(): Quote
    {
        return (new Quote())
            ->setOriginCompanyId(123)
            ->setBaseSigningId(99)
            ->setResolvedState(ESigningState::SOLD);
    }
}
