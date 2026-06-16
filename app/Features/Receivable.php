<?php

namespace App\Features;

use App\Features\Traits\ResolvesFromPlan;

class Receivable
{
    public $name = 'receivable';

    use ResolvesFromPlan;
}
