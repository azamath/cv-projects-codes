<?php

namespace Tests\Unit;

use App\ItemSubscription;
use Tests\TestCase;

class ItemSubscriptionTest extends TestCase
{
	public function testIsActive()
	{
		$model = new ItemSubscription();
		$this->assertFalse($model->isActive());

		$model->exists = true;
		$this->assertTrue($model->isActive());

		$model->ends_at = now()->addMinute();
		$this->assertTrue($model->isActive());

		$model->ends_at = now()->subMinute();
		$this->assertFalse($model->isActive());
	}
}
