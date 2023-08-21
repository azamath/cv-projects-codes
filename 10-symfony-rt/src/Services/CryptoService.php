<?php

namespace App\Services;

class CryptoService
{
    private KeysProvider $keysProvider;

    public function __construct(KeysProvider $keysProvider)
    {
        $this->keysProvider = $keysProvider;
    }

    /**
     * Creates signature using own private keys
     *
     * @param string $data
     * @return string
     * @throws \Exception
     */
    public function createSignature(string $data): string
    {
        //variable the encrypted signature is saved in
        $signature = null;
        $privateKey = $this->getPrivateKey();
        if (!openssl_sign($data, $signature, $privateKey, 'sha256')) {
            throw new \RuntimeException(
                'Could not sign a data using current private key.'
            );
        }

        return base64_encode($signature);
    }

    /**
     * Verifies signature for given data using given public key
     *
     * @param string $signature
     * @param string $publicKey
     * @param string $data
     * @return bool
     */
    public function verifySignature(string $signature, string $publicKey, string $data): bool
    {
        $verification = openssl_verify(
            $data,
            base64_decode($signature),
            $publicKey,
            'sha256'
        );

        return 1 === $verification;
    }

    /**
     * Verifies signature created by remote system using its public key
     *
     * @param string $signature
     * @param int $companyRemoteId
     * @param string $data
     * @return bool
     */
    public function verifySignatureForRemote(string $signature, int $companyRemoteId, string $data): bool
    {
        $publicKey = $this->keysProvider->getPublicKeyByRemoteId($companyRemoteId);

        return $this->verifySignature($signature, $publicKey, $data);
    }

    /**
     * @return \OpenSSLAsymmetricKey
     * @throws \Exception
     */
    protected function getPrivateKey(): \OpenSSLAsymmetricKey
    {
        $privateKey = openssl_pkey_get_private(
            $this->keysProvider->getPrivateKey(),
            $this->keysProvider->getPrivatePass(),
        );
        if (!$privateKey) {
            throw new \RuntimeException(
                'Could not access private key with given passkey!'
            );
        }
        return $privateKey;
    }
}
