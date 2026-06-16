<?php

namespace App\Features;

use App\Features\Traits\ResolvesFromPlan;

class Member
{
    public $name = 'member';

    use ResolvesFromPlan;
}
