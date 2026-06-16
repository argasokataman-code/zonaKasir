<?php

namespace App\Features;

use App\Features\Traits\ResolvesFromPlan;

class Purchasing
{
    public $name = 'purchasing';

    use ResolvesFromPlan;
}
