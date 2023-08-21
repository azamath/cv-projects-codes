<?php

namespace App\Services;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class SignatureValidatorService
{
    const SIGNATURE_HEADER = 'signature';
    const SENDER_HEADER = 'sender_id';

    private CryptoService $cryptoService;

    public function __construct(CryptoService $cryptoService)
    {
        $this->cryptoService = $cryptoService;
    }

    public function validate(Request $request): void
    {
        $signature = $request->headers->get(self::SIGNATURE_HEADER);
        $companyRemoteId = (int)$request->headers->get(self::SENDER_HEADER);

        if (!$signature || !$companyRemoteId) {
            $this->fail();
        }

        if ($this->cryptoService->verifySignatureForRemote($signature, $companyRemoteId, $request->getContent())) {
            return;
        }

        $this->fail();
    }

    protected function fail(): void
    {
        throw new AccessDeniedHttpException('This action needs a valid signature!');
    }
}
