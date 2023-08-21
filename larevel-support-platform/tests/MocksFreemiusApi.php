<?php

namespace Tests;

use App\Components\FreemiusApi;
use Mockery\MockInterface;

trait MocksFreemiusApi
{
	protected function freemiusApiShouldReturnValidUser()
	{
		$mock = $this->freemiusMock();
		$mock->shouldReceive('users')
			->andReturn([
				(object) [
					'id' => '100000',
					'email' => 'customer@freemius.com',
					'public_key' => 'pk_00000',
					'secret_key' => 'sk_00000',
				]
			]);

		return $this;
	}

	protected function freemiusApiShouldReturnValidRecentLicenses()
	{
		$mock = $this->freemiusMock();
		$mock->shouldReceive('userRecentLicenses')
			->andReturn((object) [
				'id' => '600000',
				'expiration' => now()->addYear()->toIso8601String(),
				'secret_key' => 'sk_XXXXXX',
			]);

		return $this;
	}

	/**
	 * @return \Mockery\MockInterface
	 */
	protected function freemiusMock()
	{
		$mock = app(FreemiusApi::class);
		if (!$mock instanceof MockInterface) {
			$mock = $this->mock(FreemiusApi::class);
		}

		return $mock;
	}
}
