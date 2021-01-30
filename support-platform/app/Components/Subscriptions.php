<?php

namespace App\Components;

use App\Exceptions\Envato\ItemMismatchException;
use App\Exceptions\Envato\PurchaseCodeException;
use App\Exceptions\Envato\UnsupportedItemException;
use App\Exceptions\Envato\UsernameMismatchException;
use App\Exceptions\HandledException;
use App\ItemSubscription;
use App\StmItem;
use App\User;
use Carbon\Carbon;
use Exception;
use GuzzleHttp\Exception\ClientException;

class Subscriptions
{
	/**
	 * @var \App\Components\EnvatoApi
	 */
	private $envatoApi;

	/**
	 * Subscriptions constructor.
	 *
	 * @param \App\Components\EnvatoApi $envatoApi
	 */
	public function __construct(EnvatoApi $envatoApi)
	{
		$this->envatoApi = $envatoApi;
	}

	/**
	 * Retrieve subscriptions for a user
	 *
	 * @param \App\User $user
	 *
	 * @return \Illuminate\Database\Eloquent\Collection|\App\ItemSubscription[]
	 */
	public function forUser(User $user)
	{
		return ItemSubscription::query()
			->where('user_id', $user->id)
			->get();
	}

	/**
	 * Retrieve subscription for an item
	 *
	 * @param \App\User $user
	 * @param \App\StmItem $item
	 *
	 * @return \App\ItemSubscription
	 */
	public function forItem(User $user, StmItem $item)
	{
		/** @var \App\ItemSubscription $found */
		$found = ItemSubscription::query()
			->firstOrNew([
				'driver' => $item->subscriptionDriver(),
				'user_id' => $user->id,
				'item_id' => $item->id,
			]);

		return $found;
	}

	/**
	 * Adds new subscription record for provided Purchase code
	 *
	 * @param \App\User $user
	 * @param string $purchase_code
	 *
	 * @return \App\ItemSubscription
	 * @throws \App\Exceptions\HandledException
	 */
	public function addByCode(User $user, $purchase_code)
	{
		$response = $this->codeInfo($purchase_code);
		/** @var \App\StmItem $item */
		$item = StmItem::supported()->where('envato_id', $response->item->id)->firstOr(function () {
			throw new UnsupportedItemException();
		});

		$this->ensureEnvatoAccount($user, $response);

		return $this->updateSubscription($user, $item, $purchase_code, $response->supported_until);
	}

	/**
	 * Adds new subscription record for provided Item and Purchase code
	 *
	 * @param \App\User $user
	 * @param \App\StmItem $item
	 * @param string $purchase_code
	 *
	 * @return \App\ItemSubscription
	 * @throws \App\Exceptions\HandledException
	 */
	public function addByItemCode(User $user, StmItem $item, $purchase_code)
	{
		$response = $this->codeInfo($purchase_code);

		if ($response->item->id != $item->envato_id) {
			throw new ItemMismatchException($item, $response->item);
		}

		$this->ensureEnvatoAccount($user, $response);

		return $this->updateSubscription($user, $item, $purchase_code, $response->supported_until);
	}

	/**
	 * Try to update with no exceptions throwing, in throttling safe mode.
	 *
	 * @param \App\ItemSubscription $subscription
	 */
	public function tryUpdate(ItemSubscription $subscription)
	{
		if ($subscription->updated_at && $subscription->updated_at->isLastMinute()) {
			// it has been recently updated
			return;
		}

		// we can't check for update non-existent Envato subscription
		if ($subscription->driver == 'envato' && !$subscription->exists) {
			return;
		}

		// we can't check for Freemius subscription without user ID
		if ($subscription->driver == 'freemius' && !$subscription->user->freemius_id) {
			return;
		}

		try {
			$this->update($subscription);
		}
		catch (Exception $e) {
		}
	}

	/**
	 * Updates/synchronizes subscription info from remote provider
	 *
	 * @param \App\ItemSubscription $subscription
	 *
	 * @return \App\ItemSubscription
	 * @throws \Exception
	 */
	public function update(ItemSubscription $subscription)
	{
		switch ($subscription->driver) {
			case 'envato':
				$this->updateEnvato($subscription);
				break;
			case 'freemius':
				$this->updateFreemius($subscription);
				break;
		}

		return $subscription;
	}

	/**
	 * Updates subscription record with new Purchase code information for given user.
	 *
	 * @param \App\User $user
	 * @param integer $item_id
	 * @param string $purchase_code
	 *
	 * @return \App\ItemSubscription
	 * @throws \Exception
	 */
	public function updateItem(User $user, $item_id, $purchase_code)
	{
		$response = $this->codeInfo($purchase_code);
		/** @var \App\StmItem $item */
		$item = StmItem::findOrFail($item_id);

		if ($response->item->id != $item->envato_id) {
			throw new ItemMismatchException($item, $response->item);
		}

		$this->ensureEnvatoAccount($user, $response);

		/** @var \App\ItemSubscription $subscription */
		$subscription = ItemSubscription::query()->where([
			'user_id' => $user->id,
			'item_id' => $item_id,
		])->firstOrFail();

		$subscription->purchase_code = $purchase_code;
		$subscription->ends_at = Carbon::parse($response->supported_until);
		$subscription->save();

		return $subscription;
	}

	/**
	 * Adds subscription info for Freemius licence
	 *
	 * @param \App\User $user
	 * @param \App\StmItem $item
	 * @param string $email
	 * @param string $publicKey
	 *
	 * @return \App\ItemSubscription
	 * @throws \App\Exceptions\Envato\UnsupportedItemException
	 * @throws \Exception
	 */
	public function addFreemius(User $user, StmItem $item, $email, $publicKey)
	{
		if (!$item->freemius_id) {
			throw new UnsupportedItemException();
		}

		$users = freemiusApi()->users($item->freemius_id, compact('email'));
		if (empty($users)) {
			throw new HandledException('Sorry, no Freemius licenses found for this item.');
		}

		$fuser = $users[0];
		if ($fuser->public_key !== $publicKey) {
			throw new HandledException('Sorry, we can not authorize your Freemius account by given public key.');
		}

		$this->ensureFreemiusAccount($user, $fuser->id);

		$license = freemiusApi()->userRecentLicenses($item->freemius_id, $fuser);
		if (!$license) {
			throw new HandledException('Sorry, no Freemius licenses found for this item.');
		}

		return $this->updateSubscription($user, $item, $license->secret_key, $license->expiration);
	}

	protected function updateEnvato(ItemSubscription $subscription): ItemSubscription
	{
		$response = $this->codeInfo($subscription->purchase_code);

		return $this->updateSubscriptionData($subscription, $subscription->purchase_code, $response->supported_until);
	}

	protected function updateFreemius(ItemSubscription $subscription): ItemSubscription
	{
		$item = $subscription->item;
		$user = $subscription->user;
		$fuser = freemiusApi()->user($item->freemius_id, $user->freemius_id);
		$license = freemiusApi()->userRecentLicenses($item->freemius_id, $fuser);

		return $this->updateSubscriptionData($subscription, $license->secret_key, $license ? $license->expiration : null);
	}

	protected function codeInfo($purchase_code)
	{
		try {
			$response = $this->envatoApi->purchaseCodeInfo($purchase_code)->json(false);
		}
		catch (HandledException $e) {
			if (strpos($e->getMessage(), 'No sale') !== false) {
				$e = new PurchaseCodeException($e->getMessage());
			}

			throw $e;
		}

		if (empty($response->supported_until)) {
			throw new UnsupportedItemException();
		}

		if (empty($response->item) || empty($response->item->id)) {
			throw new HandledException('Something wrong with your Purchase Code. Please retry.', 422);
		}

		return $response;
	}

	protected function updateSubscription(User $user, StmItem $item, $code, $ends_at): ItemSubscription
	{
		/** @var ItemSubscription $subscription */
		$subscription = ItemSubscription::query()->firstOrNew([
			'driver' => $item->subscriptionDriver(),
			'user_id' => $user->id,
			'item_id' => $item->id,
		]);

		return $this->updateSubscriptionData($subscription, $code, $ends_at);
	}

	protected function updateSubscriptionData(ItemSubscription $subscription, $code, $ends_at)
	{
		$subscription->forceFill([
			'purchase_code' => $code,
			'ends_at' => $ends_at ? Carbon::parse($ends_at) : null,
		]);

		// when auto syncing expired subscriptions it checks updated_at value
		// in order to prevent throttling API
		// using touch() to ensure the timestamps are fresh
		$subscription->touch();

		return $subscription;
	}

	/**
	 * @param \App\User $user
	 * @param object $response
	 *
	 * @throws \App\Exceptions\Envato\UsernameMismatchException
	 */
	protected function ensureEnvatoAccount(User $user, $response): void
	{
		$envato_username = $response->buyer;
		if ($user->envato_username && $user->envato_username != $envato_username) {
			throw new UsernameMismatchException(
				"Sorry, the purchase code linked to another username: {$envato_username}. Your username is: {$user->envato_username}"
			);
		}

		if (!$user->envato_username) {
			if (User::query()->where('envato_username', $envato_username)->exists()) {
				throw new UsernameMismatchException('Sorry, this Envato account is already exists in our database.');
			}

			$user->envato_username = $envato_username;
			$user->save();
		}
	}

	/**
	 * @param \App\User $user
	 * @param mixed $freemius_id
	 *
	 * @throws \Exception
	 */
	protected function ensureFreemiusAccount(User $user, $freemius_id): void
	{
		if ($user->freemius_id && $user->freemius_id != $freemius_id) {
			throw new HandledException('Sorry, your are already linked to another Freemius user.');
		}

		if (!$user->freemius_id) {
			if (User::query()->where('freemius_id', $freemius_id)->exists()) {
				throw new HandledException('Sorry, this Freemius account is already exists in our database.');
			}

			$user->freemius_id = $freemius_id;
			$user->save();
		}
	}
}
