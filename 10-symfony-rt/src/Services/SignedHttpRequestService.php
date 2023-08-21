<?php


namespace App\Services;

use App\Repository\CompanyRepository;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class SignedHttpRequestService
{
    private HttpClientInterface $client;
    private CompanyRepository $companyRepository;
    private CryptoService $cryptoService;

    public function __construct(
        HttpClientInterface $client,
        CompanyRepository $companyRepository,
        CryptoService $cryptoService,
    )
    {
        $this->client = $client;
        $this->companyRepository = $companyRepository;
        $this->cryptoService = $cryptoService;
    }

    /**
     * Perform a signed request with self-company ID. Return parsed JSON response as array.
     *
     * @param string $method
     * @param string $url
     * @param mixed $data
     * @return array
     * @throws \Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface
     */
    public function fetch(string $method, string $url, mixed $data): array
    {
        $senderId = $this->companyRepository->findCompanySelf()->getRemoteCompanyId();
        $body = json_encode($data);
        $signature = $this->cryptoService->createSignature(
            $body,
        );

        $response = $this->client->request(
            $method,
            $url,
            [
                'body' => $body,
                'headers' => [
                    'User-Agent' => 'RT app2',
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                    SignatureValidatorService::SENDER_HEADER => $senderId,
                    SignatureValidatorService::SIGNATURE_HEADER => $signature,
                ],
                'max_redirects' => 3,
            ],
        );

        return $response->toArray();
    }
}
