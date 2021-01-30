<?php

namespace Tests;

use App\ItemSubscription;
use App\StmItem;
use App\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\DatabaseMigrations;

trait Stubs
{
	use DatabaseMigrations;

	/** @var \App\User */
	protected $customer;

	/** @var \App\StmItem[]|\Illuminate\Database\Eloquent\Collection */
	protected $items;

	/** @var \App\StmItem[]|\Illuminate\Database\Eloquent\Collection */
	protected $itemsSupported;

	/** @var \App\ItemSubscription[]|\Illuminate\Database\Eloquent\Collection */
	protected $itemSubscriptions;

	protected function createCustomer($attributes = [])
	{
		if (!$this->customer) {
			$this->customer = factory(User::class)->create($attributes);
		}

		return $this;
	}

	protected function actingAsCustomer($attributes = [])
	{
		$this->createCustomer($attributes)->actingAs($this->customer);

		return $this;
	}

	protected function createItems()
	{
		if (!$this->items) {
			$this->itemsSupported = factory(StmItem::class)
				->states('supported', 'envato')
				->times(2)
				->create();

			$this->items = $this->itemsSupported->merge(factory(StmItem::class)
				->states('envato')
				->times(2)
				->create()
			);
		}

		return $this;
	}

	protected function createItemSubscriptions()
	{
		if (!$this->itemSubscriptions) {
			$this->createCustomer()->createItems();

			$this->itemSubscriptions = new Collection([
				factory(ItemSubscription::class)->create([
					'user_id' => $this->customer->id,
					'item_id' => $this->itemsSupported[0]->id,
				]),
				factory(ItemSubscription::class)->state('expired')->create([
					'user_id' => $this->customer->id,
					'item_id' => $this->itemsSupported[1]->id,
				]),
			]);
		}

		return $this;
	}
}
