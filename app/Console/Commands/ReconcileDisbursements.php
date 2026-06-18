<?php

namespace App\Console\Commands;

use App\Models\Tenants\Withdrawal;
use App\Models\Tenants\LedgerEntry;
use App\Services\Tenants\LedgerService;
use App\Services\Tenants\DisbursementProvider;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ReconcileDisbursements extends Command
{
    protected $signature = 'disbursements:reconcile
        {--dry-run : Only log, no updates}
        {--withdrawal= : Single withdrawal ID to check}';

    protected $description = 'Check stuck disbursements status from Flip API and reconcile';

    public function handle(LedgerService $ledger, DisbursementProvider $disbursement): int
    {
        $query = Withdrawal::whereNotNull('disburse_id')
            ->whereIn('status', ['processing', 'approved']);

        if ($id = $this->option('withdrawal')) {
            $query->where('id', $id);
        }

        $withdrawals = $query->select('id', 'status', 'disburse_id', 'amount')->get();

        if ($withdrawals->isEmpty()) {
            $this->info('No stuck disbursements found.');
            return 0;
        }

        $dryRun = $this->option('dry-run');
        $this->info("Found {$withdrawals->count()} withdrawal(s) to reconcile.");

        foreach ($withdrawals as $w) {
            $this->line("  #{$w->id} disburse_id={$w->disburse_id} status={$w->status}");

            try {
                $result = $disbursement->status($w->disburse_id);
                $flipStatus = $result['status'] ?? 'unknown';

                $this->line("    Flip status: {$flipStatus}");

                $newStatus = match ($flipStatus) {
                    'DONE' => 'completed',
                    'FAILED', 'CANCELLED' => 'failed',
                    default => null,
                };

                if (! $newStatus) {
                    $this->line("    Still processing, skip.");
                    continue;
                }

                if ($newStatus === $w->status) {
                    $this->line("    Already {$newStatus}, skip.");
                    continue;
                }

                if ($dryRun) {
                    $this->warn("    Would update: status {$w->status} → {$newStatus}");
                    continue;
                }

                $w->update([
                    'status' => $newStatus,
                    'disburse_response' => $result,
                    'processed_at' => now(),
                ]);

                if ($newStatus === 'failed') {
                    $ledger->entry(
                        ledgerableType: Withdrawal::class,
                        ledgerableId: $w->id,
                        entryType: 'credit',
                        amount: $w->amount,
                        description: "Withdrawal rollback #{$w->id}: reconcile {$flipStatus}",
                        referenceType: 'withdrawal_rollback',
                        referenceId: $w->id,
                    );
                    $this->warn("    Rolled back #{$w->id}, balance restored.");
                } elseif ($newStatus === 'completed') {
                    LedgerEntry::where('reference_type', 'withdrawal_request')
                        ->where('reference_id', $w->id)
                        ->update(['reference_type' => 'withdrawal_complete']);
                    $this->info("    Completed #{$w->id}.");
                }

                Log::info('Disbursement reconciled via command', [
                    'withdrawal_id' => $w->id,
                    'old_status' => $w->status,
                    'new_status' => $newStatus,
                    'flip_status' => $flipStatus,
                ]);
            } catch (\Throwable $e) {
                $this->error("    Error: {$e->getMessage()}");
                Log::error('Disbursement reconcile failed', [
                    'withdrawal_id' => $w->id,
                    'disburse_id' => $w->disburse_id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->info('Done.');

        return 0;
    }
}
