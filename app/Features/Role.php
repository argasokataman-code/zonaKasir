<?php

namespace App\Features;

use App\Features\Traits\ResolvesFromPlan;

class Role
{
    public $name = 'role';

    use ResolvesFromPlan;
}
