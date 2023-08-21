<?php
/**
 * Author: Azamat Holmeer
 */

namespace App\Enum;

enum EMarginValueType: int
{
    /**
     * Margin value of percentage type (e.g. 2.34 %)
     */
    case PERCENTAGE = 0;

    /**
     * Margin value of absolute type (e.g. 2.34 SEK)
     */
    case ABSOLUTE = 1;

}
