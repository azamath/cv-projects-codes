<?php

namespace App\Tests\Unit\Services;

use App\Repository\CompanyRepository;
use App\Repository\SystemRepository;
use App\Services\CryptoService;
use App\Services\KeysProvider;
use App\Services\SignatureValidatorService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class SignatureValidatorServiceTest extends TestCase
{

    public function testValidateFailsNoHeaders1()
    {
        $service = $this->getService();
        $request = new Request();
        $this->expectException(AccessDeniedHttpException::class);
        $service->validate($request);
    }

    public function testValidateFailsNoHeaders2()
    {
        $service = $this->getService();
        $request = new Request();
        $request->headers->set($service::SIGNATURE_HEADER, 'some signature');
        $this->expectException(AccessDeniedHttpException::class);
        $service->validate($request);
    }

    public function testValidateFailsWrongSignature()
    {
        $payload = json_encode(['state' => 1]);
        $request = new Request([], [], [], [], [], [], $payload);
        $request->headers->set(SignatureValidatorService::SIGNATURE_HEADER, $this->createSignatureForSystem('SystemB', $payload));
        $request->headers->set(SignatureValidatorService::SENDER_HEADER, 2);

        $service = $this->getService();
        $this->expectException(AccessDeniedHttpException::class);
        $service->validate($request);
    }

    public function testValidateOk()
    {
        $payload = json_encode(['state' => 1]);
        $request = new Request([], [], [], [], [], [], $payload);
        $request->headers->set(SignatureValidatorService::SIGNATURE_HEADER, $this->createSignatureForSystem('SystemA', $payload));
        $request->headers->set(SignatureValidatorService::SENDER_HEADER, 2);

        $service = $this->getService();
        $service->validate($request);
        $this->assertTrue(true);
    }

    /**
     * @return SignatureValidatorService
     */
    protected function getService(): SignatureValidatorService
    {
        $keyProvider = $this->createMock(KeysProvider::class);
        $keyProvider->expects($this->any())
            ->method('getPublicKeyByRemoteId')
            ->willReturn(\file_get_contents('tests/data/keys/SystemA/public.key'));

        return new SignatureValidatorService(new CryptoService($keyProvider));
    }

    /**
     * @param string $systemPath
     * @param string $data Data to sign
     *
     * @return string
     */
    protected function createSignatureForSystem(string $systemPath, string $data): string
    {
        $keysProvider = new KeysProvider(
            "tests/data/keys/{$systemPath}",
            $this->createMock(CompanyRepository::class),
            $this->createMock(SystemRepository::class),
        );

        return (new CryptoService($keysProvider))->createSignature(
            $data,
        );
    }
}
