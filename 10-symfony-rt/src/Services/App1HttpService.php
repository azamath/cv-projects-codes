<?php

namespace App\Services;

use App\Traits\HasLogger;
use Psr\Log\LoggerAwareInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class App1HttpService implements LoggerAwareInterface
{
    use HasLogger;

    public function __construct(private string $app1HostnameInternal, private HttpClientInterface $client)
    {
    }

    public function getCurrentUser(string $sessionId): ?array
    {
        $this->logDebug("Requesting app1 for current user with sessionId: ${sessionId}");
        $response = $this->client->request(
            'GET',
            "http://{$this->app1HostnameInternal}/users/current",
            [
                'query' => [
                    '_dc' => microtime(true) * 1000,
                ],
                'headers' => [
                    'Cookie' => 'PHPSESSID=' . $sessionId,
                ],
            ],
        );

        $result = $response->toArray();
        $this->logDebug("Requesting app1 result: " . json_encode($result));

        if (!isset($result['data']) || !isset($result['data'][0])) {
            return null;
        }

        return $result['data'][0];
    }
}
