<?php

namespace App\Features;

use App\Features\Traits\ResolvesFromPlan;

class Voucher
{
    public $name = 'voucher';

    use ResolvesFromPlan;
}
