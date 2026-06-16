<?php

namespace App\Features;

use App\Features\Traits\ResolvesFromPlan;

class ProductInitialPrice
{
    public $name = 'product-initial-price';

    use ResolvesFromPlan;
}
