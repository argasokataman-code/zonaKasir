<?php

namespace App\Features;

use App\Features\Traits\ResolvesFromPlan;

class PaymentShortcutButton
{
    public $name = 'payment-shortcut-button';

    use ResolvesFromPlan;
}
