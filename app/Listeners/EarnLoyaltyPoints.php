<?php

namespace App\Listeners;

use App\Events\SellingCreated;
use App\Services\Tenants\LoyaltyPointService;

class EarnLoyaltyPoints
{
    public function __construct(private LoyaltyPointService $loyaltyPointService) {}

    public function handle(SellingCreated $event): void
    {
        $this->loyaltyPointService->earnPoints($event->selling);
    }
}
