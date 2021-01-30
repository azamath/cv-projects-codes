<?php

namespace App\Enums;

use Konekt\Enum\Enum;

/**
 * Class ItemType
 *
 * @method boolean isTheme()
 * @method boolean isPlugin()
 * @method boolean isApp()
 * @method static \App\Enums\ItemType THEME()
 * @method static \App\Enums\ItemType PLUGIN()
 * @method static \App\Enums\ItemType APP()
 */
class ItemType extends Enum
{
	const THEME = 'theme';
	const PLUGIN = 'plugin';
	const APP = 'app';
	const TEMPLATE = 'template';

	const __DEFAULT = self::THEME;

	protected static $labels = [
		self::THEME => 'Theme',
		self::PLUGIN => 'Plugin',
		self::APP => 'Mobile App',
		self::TEMPLATE => 'HTML template',
	];

	/**
	 * @return string
	 */
	public function __toString()
	{
		return $this->value();
	}
}
