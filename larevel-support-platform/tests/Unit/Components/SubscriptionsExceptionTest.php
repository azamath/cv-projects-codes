<?php

namespace Tests\Unit\Components;

use App\Exceptions\Envato\PurchaseCodeException;
use App\Exceptions\Envato\UnsupportedItemException;
use App\Exceptions\Envato\UsernameMismatchException;
use App\StmItem;
use App\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\MocksEnvatoApi;
use Tests\TestCase;

class SubscriptionsExceptionTest extends TestCase
{
	use DatabaseMigrations, MocksEnvatoApi;

	/** @var \App\StmItem */
	protected $item;

	/** @var \App\User */
	protected $user;

	protected function setUp(): void
	{
		parent::setUp();

		/** @var \App\StmItem $item */
		$this->item = factory(StmItem::class)
			->states('supported', 'envato')
			->create();
		$this->setEnvatoItemId($this->item->envato_id);

		/** @var \App\User $user */
		$this->user = factory(User::class)->create([
			'envato_username' => $this->faker->userName,
		]);
	}

	public function testPurchaseCodeException()
	{
		$this->envatoApiShouldReturnUnknownPurchase();
		$this->expectException(PurchaseCodeException::class);

		subscriptions()->addByCode($this->user, $this->faker->uuid);
	}

	public function testUnsupportedItemException()
	{
		$this->setEnvatoItemId($this->faker->randomNumber());
		$this->envatoApiShouldReturnValidPurchase();
		$this->expectException(UnsupportedItemException::class);

		subscriptions()->addByCode($this->user, $this->faker->uuid);
	}

	public function testUnsupportedItemExceptionOfExistingItem()
	{
		$this->item->forceFill(['is_supported' => false])->save();
		$this->envatoApiShouldReturnValidPurchase();
		$this->expectException(UnsupportedItemException::class);

		subscriptions()->addByCode($this->user, $this->faker->uuid);
	}

	public function testUsernameExceptionOnAdd()
	{
		$this->envatoApiShouldReturnValidPurchase()
			->expectException(UsernameMismatchException::class);

		subscriptions()->addByCode($this->user, $this->faker->uuid);
	}

	public function testUsernameExceptionOnAddByItem()
	{
		$this->envatoApiShouldReturnValidPurchase()
			->expectException(UsernameMismatchException::class);

		subscriptions()->addByItemCode($this->user, $this->item, $this->faker->uuid);
	}

	public function testUsernameExceptionOnUpdate()
	{
		$this->envatoApiShouldReturnValidPurchase()
			->expectException(UsernameMismatchException::class);

		subscriptions()->updateItem($this->user, $this->item->id, $this->faker->uuid);
	}
}
