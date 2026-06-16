<?php

namespace App\Features;

use App\Features\Traits\ResolvesFromPlan;

class Supplier
{
    public $name = 'supplier';

    use ResolvesFromPlan;
}
