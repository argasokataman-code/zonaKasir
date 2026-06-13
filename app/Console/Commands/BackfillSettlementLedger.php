<?php

namespace App\Console\Commands;

use App\Models\Tenants\MidtransPayment;
use App\Services\Tenants\MidtransGatewayService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class BackfillSettlementLedger extends Command
{
    protected $signature = 'app:backfill-settlement-ledger';

    protected $description = 'Create missing ledger entries for settlements that have no ledger records';

    public function handle(): int
    {
        $settlements = MidtransPayment::where('status', 'settlement')
            ->where('selling_id', '>', 0)
            ->get();

        $missing = $settlements->filter(function ($payment) {
            return \App\Models\Tenants\LedgerEntry::where('reference_type', 'selling')
                ->where('reference_id', $payment->selling_id)
                ->where('entry_type', 'credit')
                ->doesntExist();
        });

        if ($missing->isEmpty()) {
            $this->info('All settlements have ledger entries. Nothing to backfill.');
            return 0;
        }

        $this->info("Found {$missing->count()} settlement(s) missing ledger entries:");
        $service = app(MidtransGatewayService::class);

        foreach ($missing as $payment) {
            $this->line("  - ID: {$payment->id}, Order: {$payment->order_id}, Amount: {$payment->gross_amount}");

            try {
                $service->confirmSettlement($payment);
                $this->info("    ✅ Ledger created for {$payment->order_id}");
            } catch (\Throwable $e) {
                $this->error("    ❌ Failed: {$e->getMessage()}");
            }
        }

        return 0;
    }
}
