<?php
/**
 * Author: Azamat Holmeer
 */

namespace App\Dto\Request;

use App\Dto\ImportQuote\Quote;
use App\Dto\Request\ImportQuoteCallback\Custom;

class ImportQuoteCallback
{
    public Custom $custom;
    /** @var Quote[] */
    public array $data;
    public $rawData;
}
