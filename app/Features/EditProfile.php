<?php

namespace App\Features;

use App\Features\Traits\ResolvesFromPlan;

class EditProfile
{
    public $name = 'edit-profile';

    use ResolvesFromPlan;
}
