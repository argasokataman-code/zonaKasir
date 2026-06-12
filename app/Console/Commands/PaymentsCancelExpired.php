<?php

namespace App\Console\Commands;

use App\Models\Tenants\MidtransPayment;
use Illuminate\Console\Command;

class PaymentsCancelExpired extends Command
{
    protected $signature = 'payments:cancel-expired';

    protected $description = 'Cancel pending transactions older than 24 hours';

    public function handle(): int
    {
        $count = MidtransPayment::where('status', 'pending')
            ->where('created_at', '<', now()->subDay())
            ->update(['status' => 'expired']);

        $this->info("Cancelled {$count} expired transactions");
        return self::SUCCESS;
    }
}
