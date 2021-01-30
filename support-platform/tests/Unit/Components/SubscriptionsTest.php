<?php

namespace Tests\Unit\Components;

use App\Exceptions\Envato\ItemMismatchException;
use App\ItemSubscription;
use App\StmItem;
use App\User;
use Tests\MocksEnvatoApi;
use Tests\MocksFreemiusApi;
use Tests\Stubs;
use Tests\TestCase;

class SubscriptionsTest extends TestCase
{
	use Stubs, MocksEnvatoApi, MocksFreemiusApi;

	public function testAddItem()
	{
		/** @var \App\User $user */
		$user = factory(User::class)->create();
		$this->assertEmpty($user->envato_username);

		$this->createItems()
			->setEnvatoItemId($this->items[0]->envato_id)
			->envatoApiShouldReturnValidPurchase();

		$subscription = subscriptions()->addByCode($user, $this->faker->uuid);
		$this->assertInstanceOf(ItemSubscription::class, $subscription);
		$this->assertEquals('envato', $subscription->driver);
		$this->assertEquals($user->id, $subscription->user_id);
		$this->assertNotEmpty($user->envato_username);
		$this->assertEquals($this->items[0]->id, $subscription->item_id);
		$this->assertTrue($subscription->isActive());
	}

	public function testDoesNotAddDuplicateItem()
	{
		/** @var \App\ItemSubscription $subscription */
		$subscription = factory(ItemSubscription::class)->create();

		$this->setEnvatoItemId($subscription->item->envato_id)
			->envatoApiShouldReturnValidPurchase();

		$subscription2 = subscriptions()->addByCode($subscription->user, $this->faker->uuid);
		$this->assertInstanceOf(ItemSubscription::class, $subscription);
		$this->assertTrue($subscription->is($subscription2));
	}

	public function testUpdate()
	{
		/** @var \App\ItemSubscription $subscription */
		$subscription = factory(ItemSubscription::class)->state('expired')->create();
		$this->assertFalse($subscription->isActive());

		$this->setEnvatoItemId($subscription->item->envato_id)
			->envatoApiShouldReturnValidPurchase();

		subscriptions()->update($subscription);
		$this->assertTrue($subscription->isActive());
	}

	public function testUpdateItem()
	{
		/** @var \App\ItemSubscription $subscription */
		$subscription = factory(ItemSubscription::class)->create();

		$this->setEnvatoItemId($subscription->item->envato_id)
			->envatoApiShouldReturnValidPurchase();

		$subscription2 = subscriptions()->updateItem($subscription->user, $subscription->item_id, $this->faker->uuid);
		$this->assertTrue($subscription->is($subscription2));
		$this->assertTrue($subscription->isActive());
	}

	public function testUpdateItemChecksEnvatoId()
	{
		/** @var \App\ItemSubscription $subscription */
		$subscription = factory(ItemSubscription::class)->create();

		$this->setEnvatoItemId($subscription->item->envato_id + 2)
			->envatoApiShouldReturnValidPurchase();

		$this->expectException(ItemMismatchException::class);
		subscriptions()->updateItem($subscription->user, $subscription->item_id, $this->faker->uuid);
	}

	public function testAddFreemius()
	{
		$this->createCustomer();
		$item = factory(StmItem::class)->states('supported', 'freemius')->create();
		$this->freemiusApiShouldReturnValidUser()
			->freemiusApiShouldReturnValidRecentLicenses();

		$subscription = subscriptions()->addFreemius($this->customer, $item, 'customer@freemius.com', 'pk_00000');

		$this->assertInstanceOf(ItemSubscription::class, $subscription);
		$this->assertEquals('freemius', $subscription->driver);
		$this->assertEquals($this->customer->id, $subscription->user_id);
		$this->assertNotEmpty($this->customer->freemius_id);
		$this->assertEquals($item->id, $subscription->item_id);
		$this->assertTrue($subscription->isActive());
	}
}
