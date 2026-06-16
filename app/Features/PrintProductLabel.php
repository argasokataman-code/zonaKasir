<?php

namespace App\Features;

use App\Features\Traits\ResolvesFromPlan;

class PrintProductLabel
{
    public $name = 'print-product-label';

    use ResolvesFromPlan;
}
