<?php

namespace App\Services\Tenants;

use App\Models\Tenants\About;
use App\Models\Tenants\Withdrawal;
use App\Models\Tenants\LedgerEntry;
use App\Notifications\TransferCompleted;
use App\Notifications\TransferFailed;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class DirectTransferService
{
    private const FEE_AMOUNT = 2500; // Flip fee per transfer
    private const MIN_TRANSFER = 50000;
    private const MAX_TRANSFER = 50000000; // Rp 50 juta (Flip limit, sesuaikan jika berubah)

    public function __construct(
        private readonly LedgerService $ledger,
        private readonly DisbursementProvider $disbursement,
        private readonly FlipDataService $flipData,
    ) {}

    /**
     * Transfer funds directly to tenant's bank account via Flip.
     *
     * @param int    $amount    Total debit dari Flip (gross amount, termasuk fee)
     * @param int    $adminId   Admin yang melakukan transfer
     * @param string $notes     Catatan opsional
     *
     * @return Withdrawal
     *
     * @throws \InvalidArgumentException
     * @throws InsufficientBalanceException
     * @throws DisbursementFailedException
     */
    public function transferToTenant(
        int $amount,
        int $adminId,
        string $notes = '',
    ): Withdrawal {
        // ── Step 1: Validate tenant bank info ──
        $about = About::first();
        if (! $about) {
            throw new \InvalidArgumentException(
                'Tenant belum mengatur informasi bank. Silakan lengkapi profil terlebih dahulu.'
            );
        }

        if (empty($about->bank_account_number) || empty($about->bank_code)) {
            throw new \InvalidArgumentException(
                'Informasi bank tidak lengkap. Silakan lengkapi: bank_name, bank_code, bank_account_number.'
            );
        }

        // ── Step 2: Validate amount ──
        if ($amount < self::MIN_TRANSFER) {
            throw new \InvalidArgumentException(
                "Minimal transfer adalah Rp " . number_format(self::MIN_TRANSFER, 0, ',', '.')
            );
        }

        if ($amount > self::MAX_TRANSFER) {
            throw new \InvalidArgumentException(
                "Maksimal transfer adalah Rp " . number_format(self::MAX_TRANSFER, 0, ',', '.')
            );
        }

        // ── Step 3: Calculate fee & net amount ──
        $feeAmount = self::FEE_AMOUNT;
        $netAmount = $amount - $feeAmount; // Tenant terima ini

        if ($netAmount <= 0) {
            throw new \InvalidArgumentException(
                'Nominal terlalu kecil. Minimal harus lebih dari fee Rp ' . number_format($feeAmount, 0, ',', '.')
            );
        }

        // ── Step 4: Check Flip balance (pre-flight) ──
        $flipBalance = $this->flipData->getBalance();
        if ($flipBalance === null) {
            throw new DisbursementFailedException(
                'Gagal memeriksa saldo Flip. Silakan coba lagi.'
            );
        }

        if (($flipBalance['balance'] ?? 0) < $amount) {
            throw new InsufficientBalanceException(
                'Saldo Flip tidak mencukupi. '
                . 'Dibutuhkan: Rp ' . number_format($amount, 0, ',', '.')
                . '. Tersedia: Rp ' . number_format($flipBalance['balance'] ?? 0, 0, ',', '.')
            );
        }

        // ── Step 5: Check ledger balance (with lock) ──
        $ledgerBalance = $this->ledger->getCurrentBalanceWithLock();
        if ($ledgerBalance < $amount) {
            throw new InsufficientBalanceException(
                'Saldo ledger tidak mencukupi. '
                . 'Dibutuhkan: Rp ' . number_format($amount, 0, ',', '.')
                . '. Tersedia: Rp ' . number_format($ledgerBalance, 0, ',', '.')
            );
        }

        // ── Step 6: Create withdrawal record + ledger entry (ATOMIC) ──
        $idempotencyKey = 'dt-' . now()->timestamp . '-' . substr(md5(random_bytes(8)), 0, 8);

        $withdrawal = DB::transaction(function () use (
            $amount, $netAmount, $feeAmount, $adminId, $notes, $idempotencyKey, $about
        ) {
            $withdrawal = Withdrawal::create([
                'type'                => 'admin_direct',
                'amount'              => $netAmount,       // Net amount (yang diterima tenant)
                'fee_amount'          => $feeAmount,       // Fee Flip
                'bank_name'           => $about->bank_name,
                'bank_account_name'   => $about->bank_account_name,
                'bank_account_number' => $about->bank_account_number,
                'bank_code'           => $about->bank_code,
                'status'              => 'processing',
                'idempotency_key'     => $idempotencyKey,
                'requested_by'        => $adminId,
                'initiated_by'        => $adminId,
                'approved_by'         => $adminId,
                'internal_notes'      => $notes,
                'processed_at'        => now(),
            ]);

            // Ledger: debit total amount (gross)
            $this->ledger->entry(
                ledgerableType: Withdrawal::class,
                ledgerableId: $withdrawal->id,
                entryType: 'debit',
                amount: $amount,
                description: "Transfer ke tenant (net: {$netAmount}, fee: {$feeAmount})",
                referenceType: 'transfer_to_tenant',
                referenceId: $withdrawal->id,
            );

            return $withdrawal;
        });

        // ── Step 7: Send to Flip ──
        try {
            $result = $this->disbursement->send([
                'bank_code'       => $about->bank_code,
                'account_number'  => $about->bank_account_number,
                'account_name'    => $about->bank_account_name,
                'amount'          => $amount,       // Gross amount (total debit)
                'remark'          => "Zonakasir Transfer #{$withdrawal->id}",
                'idempotency_key' => $idempotencyKey,
            ]);

            // ── Step 8: Handle Flip response status ──
            $newStatus = match ($result['status'] ?? 'pending') {
                'DONE'    => 'completed',
                'PENDING' => 'processing',
                'FAILED', 'CANCELLED' => 'failed',
                default   => 'processing',
            };

            $withdrawal->update([
                'status'            => $newStatus,
                'disburse_id'       => $result['id'] ?? null,
                'disburse_response' => $result,
                'processed_at'      => now(),
            ]);

            // ── Step 9: Rollback if failed ──
            if ($newStatus === 'failed') {
                $this->rollbackLedger($withdrawal, $amount);
            }

            // ── Step 10: Notify admin (transfer completed) ──
            if ($newStatus === 'completed') {
                Notification::send(
                    $withdrawal->requestedBy,
                    new TransferCompleted($withdrawal)
                );
            }

        } catch (\Throwable $e) {
            Log::error('DirectTransfer: Flip API failed', [
                'withdrawal_id' => $withdrawal->id,
                'error'         => $e->getMessage(),
            ]);

            $withdrawal->update([
                'status'            => 'failed',
                'disburse_response' => ['error' => $e->getMessage()],
            ]);

            $this->rollbackLedger($withdrawal, $amount);

            // Notify admin of failure
            Notification::send(
                $withdrawal->requestedBy,
                new TransferFailed($withdrawal)
            );

            throw new DisbursementFailedException(
                'Transfer gagal: ' . $e->getMessage(),
                previous: $e,
            );
        }

        return $withdrawal->fresh();
    }

    private function rollbackLedger(Withdrawal $withdrawal, int $grossAmount): void
    {
        $this->ledger->entry(
            ledgerableType: Withdrawal::class,
            ledgerableId: $withdrawal->id,
            entryType: 'credit',
            amount: $grossAmount,
            description: "Rollback transfer gagal #{$withdrawal->id}",
            referenceType: 'transfer_rollback',
            referenceId: $withdrawal->id,
        );
    }
}
