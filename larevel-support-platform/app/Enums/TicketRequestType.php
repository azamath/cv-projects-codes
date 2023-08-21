<?php

namespace App\Enums;

use Konekt\Enum\Enum;

/**
 * Class TicketRequestType
 *
 * @method boolean isSupport()
 * @method boolean isPreSale()
 * @method static \App\Enums\TicketRequestType SUPPORT()
 * @method static \App\Enums\TicketRequestType PRE_SALE()
 */
class TicketRequestType extends Enum
{
	const UNKNOWN = null;
	const SUPPORT = 'support';
	const PRE_SALE = 'pre-sale';
	const __DEFAULT = self::UNKNOWN;

	protected static $labels = [
		self::UNKNOWN => 'Unknown',
		self::SUPPORT => 'Support Request',
		self::PRE_SALE => 'Pre-sale question / Feature request',
	];

	public static function choices()
	{
		$choices = parent::choices();
		unset($choices[self::UNKNOWN]);

		return $choices;
	}
}
