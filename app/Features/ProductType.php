<?php

namespace App\Features;

use App\Features\Traits\ResolvesFromPlan;

class ProductType
{
    public $name = 'product-type';

    use ResolvesFromPlan;
}
