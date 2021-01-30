<?php

namespace App\Components;

use App\Exceptions\HandledException;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Middleware;
use Psr\Http\Message\ResponseInterface;

class FreemiusApi
{

	protected $scope = 'developers';

	protected $scopeId = '';

	protected $publicKey = '';

	protected $secretKey = '';

	protected $exceptions = true;

	protected $token = null;

	public function __construct()
	{
		$this->scope = 'developers';
		$this->scopeId = config('services.freemius.developer_id');
		$this->publicKey = config('services.freemius.public_key');
		$this->secretKey = config('services.freemius.secret_key');
	}

	public function plugins($query = [], $fetch = true)
	{
		$result = $this
			->request('GET', "/plugins.json", $query)
			->json();

		return $fetch ? $result->plugins : $result;
	}

	public function users($pluginId, $query = [], $fetch = true)
	{
		$result = $this
			->request('GET', "/plugins/{$pluginId}/users.json", $query)
			->json();

		return $fetch ? $result->users : $result;
	}

	public function user($pluginId, $userId)
	{
		return $this
			->request('GET', "/plugins/{$pluginId}/users/{$userId}.json")
			->json();
	}

	public function userRecentLicenses($pluginId, $user)
	{
		$res = $this
			->scope('users', $user->id, $user->public_key, $user->secret_key)
			->request('GET', "/plugins/{$pluginId}/licenses.json", ['filter' => 'all']);

		return collect($res->json()->licenses)
			->sortByDesc(function ($item) {
				return strtotime($item->expiration);
			})
			->first();
	}

	/**
	 * @param string $method  HTTP request method
	 * @param string $uri  Request URI part after scope /{scope}/{scopeId}
	 * @param array $params  Depending on method. GET: query params. POST: body params.
	 *
	 * @return \App\Components\JsonAwareResponse
	 * @throws \App\Exceptions\HandledException
	 */
	public function request($method, $uri, $params = [])
	{
		$base_uri = config('services.freemius.url', 'https://api.freemius.com');
		$uri = "/v1/{$this->scope}/{$this->scopeId}/" . ltrim($uri, '/');
		$method = strtoupper($method);
		$content_type = $content_md5 = '';
		$date = date('r');
		$headers = [];

		if (in_array($method, ['POST', 'PUT'])) {
			$content_type = 'application/json';

			if (!empty($params)) {
				$content_md5 = md5(json_encode($params));
				$headers['Content-MD5'] = $content_md5;
			}
		}

		$headers['Content-Type'] = $content_type;

		if ($this->token) {
			$headers['Authorization'] = "FSA {$this->token}";
		}
		elseif (strpos($uri, 'ping.json') === false) {
			$signed_uri = explode('?', $uri);
			$signed_uri = $signed_uri[0];
			$sign = "{$method}\n$content_md5\n{$content_type}\n$date\n$signed_uri";
			$sign = str_replace( '=', '', strtr(base64_encode(hash_hmac('sha256', $sign, $this->secretKey)), '+/', '-_' ));
			$headers['Date'] = $date;
			$headers['Authorization'] = "FS {$this->scopeId}:{$this->publicKey}:{$sign}";
		}

		$client = new Client(compact('base_uri'));
		/** @var \GuzzleHttp\HandlerStack $handler */
		$handler = $client->getConfig('handler');
		$handler->push(
			Middleware::mapResponse(function (ResponseInterface $response) {
				return new JsonAwareResponse(
					$response->getStatusCode(),
					$response->getHeaders(),
					$response->getBody(),
					$response->getProtocolVersion(),
					$response->getReasonPhrase()
				);
			}),
			'json_decode_middleware'
		);

		$options = [
			'query' => $params,
			'headers' => $headers,
			'http_errors' => $this->exceptions,
		];

		try {
			/** @var \App\Components\JsonAwareResponse $response */
			$response = $client->request($method, $uri, $options);
		}
		catch (GuzzleException $e) {
			report($e);
			throw new HandledException(
				'Sorry, we are having technical difficulties with Freemius connection, ' .
				'and are actively working on a fix.',
				0, $e);
		}

		return $response;
	}

	/**
	 * @param string $scope
	 * @param string $scopeId
	 * @param string $publicKey
	 * @param string $secretKey
	 *
	 * @return FreemiusApi
	 */
	public function scope($scope, $scopeId, $publicKey, $secretKey)
	{
		$this->scope = $scope;
		$this->scopeId = $scopeId;
		$this->publicKey = $publicKey;
		$this->secretKey = $secretKey;

		return $this;
	}

	/**
	 * @param bool $exceptions
	 *
	 * @return FreemiusApi
	 */
	public function setExceptions(bool $exceptions)
	{
		$this->exceptions = $exceptions;

		return $this;
	}

	/**
	 * @param string $token
	 *
	 * @return FreemiusApi
	 */
	public function setToken($token)
	{
		$this->token = $token;

		return $this;
	}
}
