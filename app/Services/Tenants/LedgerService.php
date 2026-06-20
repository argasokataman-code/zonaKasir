<?php

namespace App\Services\Tenants;

use App\Models\Tenants\LedgerEntry;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class InsufficientBalanceException extends \RuntimeException {}

class LedgerService
{
    /**
     * Create ledger entry with database-level advisory lock for row-level concurrency safety.
     */
    public function entry(
        string $ledgerableType,  // Selling::class, Withdrawal::class
        int $ledgerableId,
        string $entryType,       // 'credit' | 'debit'
        int $amount,             // whole Rupiah, no decimals
        string $description,
        string $referenceType,
        int $referenceId,
        ?string $feeRateType = null,
        ?int $feeRateValue = null,
    ): LedgerEntry
    {
        $lockName = 'ledger_' . Str::slug($ledgerableType) . '_' . $ledgerableId;

        // Database-level advisory lock (works on both MySQL and PostgreSQL)
        $this->acquireLock($lockName, 5);

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
            $this->releaseLock($lockName);
        }
    }

    public function getCurrentBalance(): int
    {
        return (int) LedgerEntry::sum(DB::raw("CASE WHEN entry_type = 'credit' THEN amount ELSE -amount END"));
    }

    /**
     * Get current balance with advisory lock to prevent concurrent reads.
     */
    public function getCurrentBalanceWithLock(): int
    {
        $lockName = 'ledger_balance_lock';
        $this->acquireLock($lockName, 10);

        try {
            return $this->getCurrentBalance();
        } finally {
            $this->releaseLock($lockName);
        }
    }

    public function getTransactions($from, $to): \Illuminate\Database\Eloquent\Collection
    {
        return LedgerEntry::select('id', 'entry_type', 'amount', 'balance_before', 'balance_after', 'description', 'reference_type', 'reference_id', 'created_at')
            ->whereBetween('created_at', [$from, $to])
            ->orderBy('id')
            ->get();
    }

    /**
     * Acquire advisory lock (supports both MySQL and PostgreSQL).
     */
    private function acquireLock(string $lockName, int $timeout): void
    {
        $driver = config('database.connections.' . config('database.default') . '.driver');

        if ($driver === 'pgsql') {
            // PostgreSQL: use pg_advisory_lock with crc32 hash
            DB::select("SELECT pg_advisory_lock(?) AS lock_acquired", [crc32($lockName)]);
        } else {
            // MySQL: use GET_LOCK
            DB::select("SELECT GET_LOCK(?, ?) AS lock_acquired", [$lockName, $timeout]);
        }
    }

    /**
     * Release advisory lock (supports both MySQL and PostgreSQL).
     */
    private function releaseLock(string $lockName): void
    {
        $driver = config('database.connections.' . config('database.default') . '.driver');

        if ($driver === 'pgsql') {
            // PostgreSQL: use pg_advisory_unlock
            DB::select("SELECT pg_advisory_unlock(?)", [crc32($lockName)]);
        } else {
            // MySQL: use RELEASE_LOCK
            DB::select("SELECT RELEASE_LOCK(?)", [$lockName]);
        }
    }
}
