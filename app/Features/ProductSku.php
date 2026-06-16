<?php

namespace App\Features;

use App\Features\Traits\ResolvesFromPlan;

class ProductSku
{
    public $name = 'product-sku';

    use ResolvesFromPlan;
}
