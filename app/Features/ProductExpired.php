<?php

namespace App\Features;

use App\Features\Traits\ResolvesFromPlan;

class ProductExpired
{
    use ResolvesFromPlan;

    public $name = 'product-expired';
}
