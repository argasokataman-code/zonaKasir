<?php

namespace App\Features;

use App\Features\Traits\ResolvesFromPlan;

class Permission
{
    public $name = 'permission';

    use ResolvesFromPlan;
}
