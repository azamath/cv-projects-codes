<?php
/**
 * Author: Azamat Holmeer
 */

namespace App\Entity;

use App\Enum\EMarginCalculation;
use App\Enum\EMarginType;
use App\Enum\EMarginValueType;

interface MarginInterface
{

    /**
     * Get Vendor ID
     * @return int|null
     */
    public function getVendorId(): ?int;

    /**
     * Get Reseller ID
     * @return int|null
     */
    public function getResellerId(): ?int;

    /**
     * Get origin company ID
     * @return int|null
     */
    public function getOriginId(): ?int;

    /**
     * Get margin value
     * @return float
     */
    public function getMarginValue(): float;

    /**
     * Get margin value type
     * @return EMarginValueType
     */
    public function getMarginValueType(): EMarginValueType;

    /**
     * Get margin calculation type
     * @return EMarginCalculation
     */
    public function getCalculationType(): EMarginCalculation;

    /**
     * Get margin type
     * @return EMarginType
     */
    public function getMarginType(): EMarginType;
}
