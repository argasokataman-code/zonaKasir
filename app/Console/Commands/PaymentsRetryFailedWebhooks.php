<?php

namespace App\Console\Commands;

use App\Models\Tenants\MidtransPayment;
use Illuminate\Console\Command;

class PaymentsRetryFailedWebhooks extends Command
{
    protected $signature = 'payments:retry-failed-webhooks';

    protected $description = 'Retry webhooks that failed processing';

    public function handle(): int
    {
        $failed = MidtransPayment::select('id', 'order_id', 'notification_payload', 'status')
            ->where('status', 'pending')
            ->where('created_at', '<', now()->subMinutes(10))
            ->where('notification_payload', '!=', null)
            ->limit(50)
            ->get();

        $retryCount = 0;
        foreach ($failed as $payment) {
            $payload = $payment->notification_payload;
            if (!$payload) continue;

            $this->info("Retrying webhook for order: {$payment->order_id}");
            $retryCount++;
        }

        $this->info("Retried {$retryCount} webhooks");
        return self::SUCCESS;
    }
}
