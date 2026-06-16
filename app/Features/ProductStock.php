<?php

namespace App\Features;

use App\Features\Traits\ResolvesFromPlan;

class ProductStock
{
    public $name = 'product-stock';

    use ResolvesFromPlan;
}
