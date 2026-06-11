<?php

namespace App\Console\Commands;

use App\Models\Subscription;
use App\Tenant;
use Illuminate\Console\Command;

class CheckBilling extends Command
{
    protected $signature = 'billing:check';

    protected $description = 'Check subscription statuses and send reminders';

    public function handle(): int
    {
        $this->info('Checking subscriptions...');

        // Expire trialing subscriptions that have ended
        $expiredTrials = Subscription::where('status', 'trialing')
            ->whereNotNull('trial_ends_at')
            ->where('trial_ends_at', '<', now())
            ->get();

        foreach ($expiredTrials as $sub) {
            $sub->update(['status' => 'expired']);
            $this->line("  Trial expired: {$sub->tenant_id}");
        }

        // Expire active subscriptions that have ended
        $ended = Subscription::where('status', 'active')
            ->whereNotNull('ends_at')
            ->where('ends_at', '<', now())
            ->get();

        foreach ($ended as $sub) {
            $sub->update(['status' => 'expired']);
            $this->line("  Subscription ended: {$sub->tenant_id}");
        }

        $this->info('Done.');
        return Command::SUCCESS;
    }
}
