<?php

namespace Tests;

use App\Components\EnvatoApi;
use App\Components\JsonAwareResponse;
use App\Exceptions\HandledException;

trait MocksEnvatoApi
{

	protected $envatoItemId;

	protected function getEnvatoItemId(): int
	{
		if (!$this->envatoItemId) {
			$this->envatoItemId = 654987;
		}

		return $this->envatoItemId;
	}

	protected function setEnvatoItemId(int $envatoItemId)
	{
		$this->envatoItemId = $envatoItemId;

		return $this;
	}

	protected function envatoApiStubActivePurchase()
	{
		return [
			'amount' => 50,
			'sold_at' => now()->subMonth()->toIso8601String(),
			'license' => 'Regular License',
			'support_amount' => '7.62',
			'supported_until' => now()->addMonths(6)->toIso8601String(),
			'item' => [
				'id' => $this->getEnvatoItemId(),
				'name' => 'MasterStudy',
			],
			'buyer' => 'buyer',
			'purchase_count' => 1,
		];
	}

	protected function envatoApiStubExpiredPurchase()
	{
		return array_merge($this->envatoApiStubActivePurchase(), [
			'supported_until' => now()->subDays(6)->toIso8601String(),
		]);
	}

	protected function envatoApiShouldReturnValidPurchase($expired = false)
	{
		/** @var \Mockery\Mock $mock */
		$mock = $this->mock(EnvatoApi::class);
		$mock->shouldReceive('purchaseCodeInfo')
			->andReturn(
				new JsonAwareResponse(
					200,
					['Content-Type' => 'application/json'],
					json_encode($expired ? $this->envatoApiStubExpiredPurchase() : $this->envatoApiStubActivePurchase())
				)
			);

		return $this;
	}

	protected function envatoApiShouldReturnUnknownPurchase()
	{
		/** @var \Mockery\Mock $mock */
		$mock = $this->mock(EnvatoApi::class);
		$mock->shouldReceive('purchaseCodeInfo')
			->andThrow(
				new HandledException('No sale belonging to the current user found with that code')
			);

		return $this;
	}
}
