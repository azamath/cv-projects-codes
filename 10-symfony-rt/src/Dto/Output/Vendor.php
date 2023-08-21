<?php
/**
 * Author: Azamat Holmeer
 */

namespace App\Dto\Output;

class Vendor
{
    public int $vendorId;

    public string $name;

    public ?string $alias;

    public string $quotesMethod;
}
