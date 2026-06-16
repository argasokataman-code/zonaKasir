<?php

namespace App\Features;

use App\Features\Traits\ResolvesFromPlan;

class User
{
    public $name = 'user';

    use ResolvesFromPlan;
}
