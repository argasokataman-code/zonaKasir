<?php

namespace App\Features;

use App\Features\Traits\ResolvesFromPlan;

class PaymentMethod
{
    public $name = 'payment-method';

    use ResolvesFromPlan;
}
