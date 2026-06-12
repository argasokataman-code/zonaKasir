<?php

namespace App\Console\Commands;

use App\Services\Tenants\ReconciliationService;
use Illuminate\Console\Command;

class PaymentsReconcile extends Command
{
    protected $signature = 'payments:reconcile
        {--date= : Date to reconcile (Y-m-d format, defaults to yesterday)}';

    protected $description = 'Run daily reconciliation against Midtrans and check ledger balance';

    public function handle(ReconciliationService $service): int
    {
        $date = $this->option('date')
            ? \Carbon\Carbon::parse($this->option('date'))
            : now()->subDay();

        $this->info("Reconciling transactions for: {$date->format('Y-m-d')}");

        $mismatches = $service->daily();

        if ($mismatches === 0) {
            $this->info('✅ Reconciliation passed: no mismatches found.');
            return self::SUCCESS;
        }

        $this->error("❌ Reconciliation found {$mismatches} mismatch(es). Check logs for details.");
        return self::FAILURE;
    }
}
