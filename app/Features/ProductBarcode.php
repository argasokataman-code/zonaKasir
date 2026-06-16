<?php

namespace App\Features;

use App\Features\Traits\ResolvesFromPlan;

class ProductBarcode
{
    public $name = 'product-barcode';

    use ResolvesFromPlan;
}
