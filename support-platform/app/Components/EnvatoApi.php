<?php

namespace App\Components;

use App\Exceptions\Envato\PurchaseCodeException;
use App\Exceptions\HandledException;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Middleware;
use Illuminate\Support\Arr;
use Psr\Http\Message\ResponseInterface;

class EnvatoApi
{
	/**
	 * Search items
	 *
	 * @param array $query
	 *
	 * @return \App\Components\JsonAwareResponse
	 * @throws \GuzzleHttp\Exception\GuzzleException
	 */
	public function searchItem($query = [])
	{
		$query = array_merge(['username' => 'stylemixthemes'], $query);

		return $this->request('GET', '/v1/discovery/search/search/item', compact('query'));
	}

	public function searchThemeforestItem($query = [])
	{
		$query['site'] = 'themeforest.net';

		return $this->searchItem($query);
	}

	public function searchCodecanyonItem($query = [])
	{
		$query['site'] = 'codecanyon.net';

		return $this->searchItem($query);
	}

	public function purchaseCodeInfo($code)
	{
		return $this->request('GET', '/v3/market/author/sale', [
			'query' => [
				'code' => $code,
			],
		]);
	}

	/**
	 * @param string $method
	 * @param string $uri
	 * @param array $options
	 *
	 * @return \App\Components\JsonAwareResponse
	 * @throws \App\Exceptions\HandledException
	 */
	protected function request($method, $uri, $options = [])
	{
		$headers = [
			'Authorization' => 'Bearer ' . config('services.envato.token'),
			'User-Agent' => 'StylemixThemes - Support'
		];

		$options['headers'] = $headers + Arr::get($options, 'headers', []);

		$client = new Client($this->baseConfig());
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

		try {
			/** @var \App\Components\JsonAwareResponse $response */
			$response = $client->request($method, $uri, $options);
		}
		catch (GuzzleException $e) {
			throw $this->processException($e);
		}

		return $response;
	}

	protected function processException($e)
	{
		if ($e instanceof ClientException) {
			/** @var \App\Components\JsonAwareResponse $response */
			$response = $e->getResponse();
			// could be when purchase code is wrong
			if (isset($response->json()->description)) {
				return new HandledException($response->json()->description, 0, $e);
			}

			// api limit reached
			if ($response->getStatusCode() === 429) {
				return new HandledException('We reached Envato API limit. Please, retry in a minute.', 0, $e);
			}
		}

		report($e);

		return new HandledException(
			'Sorry, we are having technical difficulties with Envato connection, ' .
			'and are actively working on a fix.',
			0, $e);
	}

	protected function baseConfig()
	{
		return [
			'base_uri' => 'https://api.envato.com',
		];
	}
}
