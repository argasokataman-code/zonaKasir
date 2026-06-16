<?php

namespace App\Features;

use App\Features\Traits\ResolvesFromPlan;

class SellingTax
{
    public $name = 'selling-tax';

    use ResolvesFromPlan;
}
