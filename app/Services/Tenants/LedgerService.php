<?php

namespace App\Services\Tenants;

use App\Models\Tenants\LedgerEntry;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class InsufficientBalanceException extends \RuntimeException {}

class LedgerService
{
    /**
     * Create ledger entry with MySQL GET_LOCK for row-level concurrency safety.
     */
    public function entry(
        string $ledgerableType,  // Selling::class, Withdrawal::class
        int $ledgerableId,
        string $entryType,       // 'credit' | 'debit'
        float $amount,           // double
        string $description,
        string $referenceType,
        int $referenceId,
        ?string $feeRateType = null,
        ?float $feeRateValue = null,
    ): LedgerEntry
    {
        $lockName = 'ledger_' . Str::slug($ledgerableType) . '_' . $ledgerableId;

        // MySQL application-level lock
        DB::select("SELECT GET_LOCK(?, 5) AS lock_acquired", [$lockName]);

        try {
            return DB::transaction(function () use (
                $ledgerableType, $ledgerableId, $entryType, $amount,
                $description, $referenceType, $referenceId,
                $feeRateType, $feeRateValue
            ) {
                $currentBalance = $this->getCurrentBalance();

                $balanceAfter = $entryType === 'credit'
                    ? $currentBalance + $amount
                    : $currentBalance - $amount;

                if ($balanceAfter < 0) {
                    throw new InsufficientBalanceException(
                        "Saldo tidak mencukupi. Tersedia: Rp " . number_format($currentBalance, 0, ',', '.')
                    );
                }

                return LedgerEntry::create([
                    'ledgerable_type'   => $ledgerableType,
                    'ledgerable_id'     => $ledgerableId,
                    'entry_type'        => $entryType,
                    'amount'            => $amount, // ALWAYS positive
                    'balance_before'    => $currentBalance,
                    'balance_after'     => $balanceAfter,
                    'description'       => $description,
                    'reference_type'    => $referenceType,
                    'reference_id'      => $referenceId,
                    'fee_rate_type'     => $feeRateType,
                    'fee_rate_value'    => $feeRateValue,
                ]);
            });
        } finally {
            DB::select("SELECT RELEASE_LOCK(?)", [$lockName]);
        }
    }

    public function getCurrentBalance(): float
    {
        return LedgerEntry::sum(DB::raw("CASE WHEN entry_type = 'credit' THEN amount ELSE -amount END"));
    }

    public function getTransactions($from, $to): \Illuminate\Database\Eloquent\Collection
    {
        return LedgerEntry::whereBetween('created_at', [$from, $to])
            ->orderBy('id')
            ->get();
    }
}
