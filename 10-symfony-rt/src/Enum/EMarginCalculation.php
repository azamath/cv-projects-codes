<?php
/**
 * Author: Azamat Holmeer
 */

namespace App\Enum;

enum EMarginCalculation: int
{
    /**
     * Calculation Type Surcharge
     */
    case SURCHARGE = 1;

    /**
     * Calculation Type Margin
     */
    case MARGIN = 2;

}
