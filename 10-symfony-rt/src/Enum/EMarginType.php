<?php
/**
 * Author: Azamat Holmeer
 */

namespace App\Enum;

enum EMarginType: int
{
    /**
     * Margin type for quote and new products
     */
    case PRODUCT = 0;

    /**
     * Margin type for upsells
     */
    case UPSELL = 1;

    /**
     * Margin type of crosssells
     */
    case CROSSSELL = 2;

}
