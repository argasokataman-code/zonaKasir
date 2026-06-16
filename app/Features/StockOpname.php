<?php

namespace App\Features;

use App\Features\Traits\ResolvesFromPlan;

class StockOpname
{
    public $name = 'stock-opname';

    use ResolvesFromPlan;
}
