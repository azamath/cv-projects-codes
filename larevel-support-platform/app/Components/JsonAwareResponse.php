<?php

namespace App\Components;

use GuzzleHttp\Psr7\Response;

class JsonAwareResponse extends Response
{
	/**
	 * Cache for performance
	 *
	 * @var array
	 */
	private $json;

	/**
	 * @param bool $assoc JSON decode associative
	 *
	 * @return array|mixed|null
	 */
	public function json($assoc = false)
	{
		if ($this->json) {
			return $this->json;
		}
		// get parent Body stream
		$body = parent::getBody();

		// if JSON HTTP header detected - then decode
		if (false !== strpos($this->getHeaderLine('Content-Type'), 'application/json')) {
			return $this->json = \json_decode($body, $assoc);
		}

		return null;
	}
}
