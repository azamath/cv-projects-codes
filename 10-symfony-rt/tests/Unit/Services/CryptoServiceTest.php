<?php

namespace App\Tests\Unit\Services;

use App\Repository\CompanyRepository;
use App\Repository\SystemRepository;
use App\Services\CryptoService;
use App\Services\KeysProvider;
use PHPUnit\Framework\TestCase;

class CryptoServiceTest extends TestCase
{

    public function testCreateSignature()
    {
        $result = (new CryptoService($this->getTestKeysProvider()))->createSignature('foo');
        $this->assertIsString($result);
    }

    public function testCreateSignatureFails()
    {
        $this->expectException(\Exception::class);
        (new CryptoService($this->getMockKeysProvider()))->createSignature('');
    }

    public function testVerifySignature()
    {
        $keysProvider = $this->getTestKeysProvider();
        $cryptoService = new CryptoService($keysProvider);
        $signature = $cryptoService->createSignature('foo');
        $result = ($cryptoService)->verifySignature($signature, $keysProvider->getPublicKey(), 'foo');
        $this->assertTrue($result);

        $keysProviderA = $this->getTestKeysProvider('SystemA');
        $signature = $cryptoService->createSignature('foo');
        $result = $cryptoService->verifySignature($signature, $keysProviderA->getPublicKey(), 'foo');
        $this->assertFalse($result);
    }

    public function testVerifySignatureForRemote()
    {
        $keysProviderA = $this->getTestKeysProvider('SystemA');
        $cryptoServiceA = new CryptoService($keysProviderA);
        $signature = $cryptoServiceA->createSignature('foo');

        $keysProvider = $this->getMockKeysProvider();
        $keysProvider->expects($this->any())
            ->method('getPublicKeyByRemoteId')
            ->willReturn($keysProviderA->getPublicKey());
        $cryptoService = new CryptoService($keysProvider);
        $result = $cryptoService->verifySignatureForRemote($signature, 1, 'foo');

        $this->assertTrue($result);
    }

    public function testVerifySignatureForRemoteFails()
    {
        $keysProviderA = $this->getTestKeysProvider('SystemA');
        $cryptoServiceA = new CryptoService($keysProviderA);
        $signature = $cryptoServiceA->createSignature('foo');

        $keysProviderB = $this->getTestKeysProvider('SystemB');
        $keysProviderMock = $this->getMockKeysProvider();
        $keysProviderMock->expects($this->any())
            ->method('getPublicKeyByRemoteId')
            ->willReturn($keysProviderB->getPublicKey());
        $cryptoService = new CryptoService($keysProviderMock);
        $result = $cryptoService->verifySignatureForRemote($signature, 1, 'foo');

        $this->assertFalse($result);
    }

    /**
     * @return KeysProvider|\PHPUnit\Framework\MockObject\MockObject
     */
    protected function getTestKeysProvider(string $system = ''): KeysProvider
    {
        return new KeysProvider(
            "tests/data/keys/$system",
            $this->createMock(CompanyRepository::class),
            $this->createMock(SystemRepository::class),
        );
    }

    /**
     * @return KeysProvider|\PHPUnit\Framework\MockObject\MockObject
     */
    protected function getMockKeysProvider(): \PHPUnit\Framework\MockObject\MockObject|KeysProvider
    {
        return $this->createMock(KeysProvider::class);
    }
}
