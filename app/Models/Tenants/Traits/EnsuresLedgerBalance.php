<?php

namespace App\Models\Tenants\Traits;

use App\Models\Tenants\LedgerEntry;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

trait EnsuresLedgerBalance
{
    /**
     * Verifies that ledger is balanced after every entry.
     * balance = SUM(credit) - SUM(debit)
     * invariant: last balance_after must equal the calculated sum
     */
    public static function bootEnsuresLedgerBalance(): void
    {
        static::created(function (LedgerEntry $entry) {
            $balance = LedgerEntry::sum(DB::raw("CASE WHEN entry_type = 'credit' THEN amount ELSE -amount END"));

            $lastEntry = LedgerEntry::orderBy('id', 'desc')
                ->value('balance_after');

            if (abs($balance - $lastEntry) > 1) {
                Log::critical('LEDGER_INVARIANT_VIOLATION', [
                    'calculated_balance' => $balance,
                    'stored_balance_after' => $lastEntry,
                    'entry_id' => $entry->id,
                ]);
            }
        });
    }
}
