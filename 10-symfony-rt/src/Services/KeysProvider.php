<?php

namespace App\Services;

use App\Repository\CompanyRepository;
use App\Repository\SystemRepository;

class KeysProvider
{
    private CompanyRepository $companyRepository;
    private SystemRepository $systemRepository;
    private string $privateKey;
    private string $privatePass;
    private string $publicKey;

    public function __construct($keysFolder, CompanyRepository $companyRepository, SystemRepository $systemRepository)
    {
        $this->privateKey = $keysFolder . '/private.key';
        $this->privatePass = $keysFolder . '/private.pass';
        $this->publicKey = $keysFolder . '/public.key';
        $this->companyRepository = $companyRepository;
        $this->systemRepository = $systemRepository;
    }

    public function getPrivateKey(): bool|string
    {
        if (!file_exists($this->privateKey)) {
            throw new \Exception('Private key was not found: ' . $this->privateKey);
        }
        return file_get_contents($this->privateKey);
    }

    public function getPrivatePass(): bool|string
    {
        if (!file_exists($this->privatePass)) {
            throw new \Exception('Password for the private key was not found: ' . $this->privatePass);
        }
        return file_get_contents($this->privatePass);
    }

    public function getPublicKey(): bool|string
    {
        if (!file_exists($this->publicKey)) {
            throw new \Exception('Public key was not found: ' . $this->publicKey);
        }
        return file_get_contents($this->publicKey);
    }

    public function getPublicKeyByRemoteId(int $companyRemoteId): string
    {
        $company = $this->companyRepository->findOneByRemoteId($companyRemoteId);
        if (!$company) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Could not resolve company with remoteId "%s"',
                    $companyRemoteId
                )
            );
        }

        $system = $this->systemRepository->findOneByCompanyId($company->getCompanyId());
        if (!$system) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Could not resolve system with companyId "%s"',
                    $company->getCompanyId()
                )
            );
        }

        return $system->getPublicKey();
    }
}
