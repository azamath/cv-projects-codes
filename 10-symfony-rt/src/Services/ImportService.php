<?php

namespace App\Services;

use App\Traits\HasLogger;
use Psr\Log\LoggerAwareInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class ImportService implements LoggerAwareInterface
{
    use HasLogger;

    public function __construct(private string $apiUrl, private string $apiToken, private HttpClientInterface $client)
    {
    }

    public function getPipelineId($fileType, $vendorCode)
    {
        $body = [
            'type' => $fileType,
            'vendor' => $vendorCode,
        ];

        $headers = [];
        $headers[] = 'User-Agent: RT app2';
        $headers[] = 'Accept: application/json';

        $response = $this->client->request(
            'POST',
            "{$this->apiUrl}/get_pipeline_id",
            [
                'body' => $body,
                'headers' => $headers,
            ],
        );

        $result = $response->toArray();
        $this->logDebug('Response from Import Service was: ' . json_encode($result));

        return $result;
    }

    public function upload(string $file, string $pipelineId, string $callbackUrl, array $customParams = []): array
    {
        $params = [
            'api_token' => $this->apiToken,
            'pipeline_id' => $pipelineId,
            'file' => \Symfony\Component\Mime\Part\DataPart::fromPath($file),
            'callback_url' => $callbackUrl,
            'custom' => $customParams,
        ];
        $formData = new \Symfony\Component\Mime\Part\Multipart\FormDataPart($params);
        $this->logDebug('Requesting Import Service with params:', $params);

        $headers = $formData->getPreparedHeaders()->toArray();
        $headers[] = 'User-Agent: RT app2';
        $headers[] = 'Accept: application/json';

        $response = $this->client->request(
            'POST',
            "{$this->apiUrl}/import_file",
            [
                'body' => $formData->bodyToString(),
                'headers' => $headers,
                'max_redirects' => 3,
            ],
        );

        $responseBody = $response->getContent();
        $this->logDebug('Import Service HTTP response: ' . $responseBody);

        return $response->toArray();
    }
}
