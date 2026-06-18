<?php

namespace App\Console\Commands;

use App\Models\Invoice;
use App\Models\Subscription;
use App\Services\InvoiceService;
use Illuminate\Console\Command;

class CheckBilling extends Command
{
    protected $signature = 'billing:check';

    protected $description = 'Check subscription statuses, generate invoices, and send reminders';

    public function handle(): int
    {
        $this->info('Checking subscriptions...');

        // Expire trialing subscriptions that have ended
        $expiredTrials = Subscription::select('id', 'status', 'tenant_id', 'trial_ends_at')
            ->where('status', 'trialing')
            ->whereNotNull('trial_ends_at')
            ->where('trial_ends_at', '<', now())
            ->get();

        foreach ($expiredTrials as $sub) {
            $sub->update(['status' => 'expired']);
            $this->line("  Trial expired: {$sub->tenant_id}");
        }

        // Expire active subscriptions that have ended
        $ended = Subscription::select('id', 'status', 'tenant_id', 'ends_at')
            ->where('status', 'active')
            ->whereNotNull('ends_at')
            ->where('ends_at', '<', now())
            ->get();

        foreach ($ended as $sub) {
            $sub->update(['status' => 'expired']);
            $this->line("  Subscription ended: {$sub->tenant_id}");
        }

        // Generate invoices for active subscriptions that expire within 7 days
        // Skip if already has a pending invoice created this week
        $renewing = Subscription::select('id', 'tenant_id', 'plan_id', 'status', 'ends_at', 'billing_cycle')
            ->where('status', 'active')
            ->whereNotNull('plan_id')
            ->whereNotNull('ends_at')
            ->where('ends_at', '>', now())
            ->where('ends_at', '<', now()->addDays(7))
            ->whereDoesntHave('invoices', function ($q) {
                $q->where('created_at', '>', now()->subWeek());
            })
            ->get();

        foreach ($renewing as $sub) {
            try {
                $invoice = app(InvoiceService::class)->createInvoice($sub, 'midtrans');
                $this->line("  Invoice generated: {$invoice->number} for {$sub->tenant_id}");
            } catch (\Throwable $e) {
                $this->error("  Failed to generate invoice for {$sub->tenant_id}: {$e->getMessage()}");
            }
        }

        $count = $expiredTrials->count() + $ended->count();
        $this->info("Done. {$count} expired, {$renewing->count()} invoices generated.");

        return Command::SUCCESS;
    }
}
