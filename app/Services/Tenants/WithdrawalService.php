<?php

namespace App\Services\Tenants;

use App\Models\Tenants\Withdrawal;
use App\Models\Tenants\About;
use App\Models\Tenants\IdempotencyLog;
use App\Models\Tenants\LedgerEntry;
use App\Notifications\WithdrawalApproved;
use App\Notifications\WithdrawalRejected;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Throwable;

class WithdrawalService
{
    public function __construct(
        private readonly LedgerService $ledger,
        private readonly DisbursementProvider $disbursement,
        private readonly FlipDataService $flipData,
    ) {}

    /**
     * Request withdrawal. Idempotency key required.
     * Auto-approves small withdrawals (< auto_approve_max) for tenants 30+ days.
     * Amounts > single_admin_max require 2 admin approvals.
     *
     * @throws InsufficientBalanceException
     */
    public function request(
        int $amount,
        string $idempotencyKey,
    ): Withdrawal {
        if (empty($idempotencyKey)) {
            throw new \InvalidArgumentException('idempotency_key is required');
        }

        $about = About::first();
        if (! $about) {
            throw new \InvalidArgumentException('Tenant bank info not configured');
        }

        if (empty($about->bank_account_number) || empty($about->bank_code)) {
            throw new \InvalidArgumentException('Informasi bank tidak lengkap');
        }

        $autoMax = config('flip.withdrawal_approval.auto_approve_max', 5000000);
        $singleAdminMax = config('flip.withdrawal_approval.single_admin_max', 25000000);
        $tenantAge = $about->created_at->diffInDays(now());

        $isAutoApprove = $amount <= $autoMax && $tenantAge >= 30 && $amount <= $singleAdminMax;

        $withdrawal = DB::transaction(function () use ($amount, $idempotencyKey, $about, $isAutoApprove) {
            $available = $this->ledger->getCurrentBalance();
            $maxAllowed = $available * 0.95;

            if ($amount > $available) {
                throw new InsufficientBalanceException(
                    __('Insufficient balance. Available: :balance', ['balance' => 'Rp ' . number_format($available, 0, ',', '.')])
                );
            }

            if ($amount > $maxAllowed) {
                throw new InsufficientBalanceException(
                    __('Maximum 95% of balance. Max: :max', ['max' => 'Rp ' . number_format($maxAllowed, 0, ',', '.')])
                );
            }

            $status = $isAutoApprove ? 'approved' : 'pending';
            $approvedBy = $isAutoApprove ? auth()->id() : null;

            $withdrawal = Withdrawal::create([
                'amount'              => $amount,
                'bank_name'           => $about->bank_name,
                'bank_account_name'   => $about->bank_account_name,
                'bank_account_number' => $about->bank_account_number,
                'bank_code'           => $about->bank_code,
                'status'              => $status,
                'idempotency_key'     => $idempotencyKey,
                'requested_by'        => auth()->id(),
                'approved_by'         => $approvedBy,
                'processed_at'        => $isAutoApprove ? now() : null,
            ]);

            $this->ledger->entry(
                ledgerableType: Withdrawal::class,
                ledgerableId: $withdrawal->id,
                entryType: 'debit',
                amount: $amount,
                description: "Withdrawal request #{$withdrawal->id}",
                referenceType: 'withdrawal_request',
                referenceId: $withdrawal->id,
            );

            IdempotencyLog::firstOrCreate([
                'idempotency_key' => $idempotencyKey,
                'status'          => 'completed',
                'endpoint'        => '/api/tenant/withdrawals',
                'method'          => 'POST',
                'response'        => json_encode(['withdrawal_id' => $withdrawal->id]),
            ]);

            return $withdrawal;
        });

        Log::info('Withdrawal created', [
            'withdrawal_id' => $withdrawal->id,
            'amount' => $withdrawal->amount,
            'status' => $withdrawal->status,
            'auto_approve' => $isAutoApprove,
        ]);

        if ($isAutoApprove) {
            try {
                $feeAmount = (int) config('flip.withdrawal_approval.fee_amount', 2500);
                $netAmount = $withdrawal->amount - $feeAmount;

                if ($netAmount > 0) {
                    $result = $this->disbursement->send([
                        'bank_code'         => $withdrawal->bank_code,
                        'account_number'    => $withdrawal->bank_account_number,
                        'account_name'      => $withdrawal->bank_account_name,
                        'amount'            => $netAmount,
                        'remark'            => 'ZK Withdrawal',
                        'idempotency_key'   => $withdrawal->idempotency_key,
                    ]);

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
                        'fee_amount'        => $feeAmount,
                    ]);

                    Log::info('Auto-approve disbursement completed', [
                        'withdrawal_id' => $withdrawal->id,
                        'status' => $newStatus,
                        'amount' => $netAmount,
                        'disburse_id' => $result['id'] ?? null,
                        'flip_status' => $result['status'] ?? 'unknown',
                    ]);
                }
            } catch (\Throwable $e) {
                Log::error('Auto-approve disbursement failed', [
                    'withdrawal_id' => $withdrawal->id,
                    'error' => $e->getMessage(),
                ]);

                $withdrawal->update([
                    'status'            => 'failed',
                    'disburse_response' => ['error' => $e->getMessage()],
                ]);

                $this->ledger->entry(
                    ledgerableType: Withdrawal::class,
                    ledgerableId: $withdrawal->id,
                    entryType: 'credit',
                    amount: $withdrawal->amount,
                    description: "Withdrawal rollback #{$withdrawal->id}: auto-approve failed",
                    referenceType: 'withdrawal_rollback',
                    referenceId: $withdrawal->id,
                );
            }
        }

        return $withdrawal->fresh();
    }

    /**
     * Approve a withdrawal.
     * - Amount > single_admin_max (25jt): requires 2 different admin approvals
     * - First approval saves approved_by, status stays pending
     * - Second approval (different admin) triggers disbursement
     *
     * @throws DisbursementFailedException
     */
    public function approve(int $withdrawalId, int $approvedBy): Withdrawal
    {
        $withdrawal = Withdrawal::where('id', $withdrawalId)
            ->lockForUpdate()
            ->firstOrFail();

        abort_if($withdrawal->status !== 'pending', 400, 'Withdrawal already processed');

        $singleAdminMax = config('flip.withdrawal_approval.single_admin_max', 25000000);

        // 2-admin approval flow for high value withdrawals
        if ($withdrawal->amount > $singleAdminMax) {
            if ($withdrawal->approved_by === null) {
                $withdrawal->update(['approved_by' => $approvedBy]);
                return $withdrawal->fresh();
            }
            abort_if($withdrawal->approved_by === $approvedBy, 400, 'Second approval must be from different admin');
            // proceed to disbursement below
        }

        try {
            $withdrawal->update(['status' => 'processing']);

            // Calculate fee (ditanggung tenant, dipotong dari nominal)
            $feeAmount = (int) config('flip.withdrawal_approval.fee_amount', 2500);
            $netAmount = $withdrawal->amount - $feeAmount;

            if ($netAmount <= 0) {
                throw new DisbursementFailedException(
                    'Nominal terlalu kecil setelah dipotong fee Rp ' . number_format($feeAmount, 0, ',', '.')
                );
            }

            // Pre-flight: Check Flip balance against net amount yg dikirim
            $flipBalance = $this->flipData->getBalance();
            if ($flipBalance === null) {
                throw new DisbursementFailedException('Gagal memeriksa saldo Flip');
            }
            if (($flipBalance['balance'] ?? 0) < $netAmount) {
                throw new DisbursementFailedException(
                    'Saldo Flip tidak mencukupi. Dibutuhkan: Rp ' . number_format($netAmount, 0, ',', '.')
                    . '. Tersedia: Rp ' . number_format($flipBalance['balance'] ?? 0, 0, ',', '.')
                );
            }

            $result = $this->disbursement->send([
                'bank_code'         => $withdrawal->bank_code,
                'account_number'    => $withdrawal->bank_account_number,
                'account_name'      => $withdrawal->bank_account_name,
                'amount'            => $netAmount,
                'remark'           => 'ZK Withdrawal',
                'idempotency_key'   => $withdrawal->idempotency_key,
            ]);

            // Handle Flip returned status (don't assume completed)
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
                'approved_by'       => $approvedBy,
                'fee_amount'        => $feeAmount,
                'processed_at'      => now(),
            ]);

            Log::info('Withdrawal approval completed', [
                'withdrawal_id' => $withdrawal->id,
                'status' => $newStatus,
                'amount' => $withdrawal->amount,
                'fee' => $feeAmount,
                'disburse_id' => $result['id'] ?? null,
                'flip_status' => $result['status'] ?? 'unknown',
                'approved_by' => $approvedBy,
            ]);

            if ($newStatus === 'failed') {
                // Rollback ledger: restore tenant balance (full requested amount)
                $this->ledger->entry(
                    ledgerableType: Withdrawal::class,
                    ledgerableId: $withdrawal->id,
                    entryType: 'credit',
                    amount: $withdrawal->amount,
                    description: "Withdrawal rollback #{$withdrawal->id}: Flip returned {$result['status']}",
                    referenceType: 'withdrawal_rollback',
                    referenceId: $withdrawal->id,
                );
            } else {
                // Update ledger reference: withdrawal_request → complete/processing
                LedgerEntry::where('reference_type', 'withdrawal_request')
                    ->where('reference_id', $withdrawal->id)
                    ->update(['reference_type' => 'withdrawal_complete']);
            }

            // Send notification
            if ($newStatus === 'completed') {
                Notification::send($withdrawal->requestedBy, new WithdrawalApproved($withdrawal));
            }

        } catch (Throwable $e) {
            $withdrawal->update([
                'status'            => 'failed',
                'disburse_response' => ['error' => $e->getMessage()],
            ]);

            $this->ledger->entry(
                ledgerableType: Withdrawal::class,
                ledgerableId: $withdrawal->id,
                entryType: 'credit',
                amount: $withdrawal->amount,
                description: "Withdrawal rollback #{$withdrawal->id}",
                referenceType: 'withdrawal_rollback',
                referenceId: $withdrawal->id,
            );

            throw new DisbursementFailedException($e->getMessage(), context: [], previous: $e);
        }

        return $withdrawal->fresh();
    }

    public function reject(int $withdrawalId, int $rejectedBy, string $reason): Withdrawal
    {
        return DB::transaction(function () use ($withdrawalId, $rejectedBy, $reason) {
            $withdrawal = Withdrawal::findOrFail($withdrawalId);
            abort_if($withdrawal->status !== 'pending', 400, 'Already processed');

            $withdrawal->update([
                'status'           => 'rejected',
                'rejected_by'     => $rejectedBy,
                'rejection_reason' => $reason,
            ]);

            $this->ledger->entry(
                ledgerableType: Withdrawal::class,
                ledgerableId: $withdrawal->id,
                entryType: 'credit',
                amount: $withdrawal->amount,
                description: "Withdrawal rejected #{$withdrawal->id}: {$reason}",
                referenceType: 'withdrawal_rejected',
                referenceId: $withdrawal->id,
            );

            Log::info('Withdrawal rejected', [
                'withdrawal_id' => $withdrawal->id,
                'amount' => $withdrawal->amount,
                'rejected_by' => $rejectedBy,
                'reason' => $reason,
            ]);

            // Send notification to tenant
            Notification::send($withdrawal->requestedBy, new WithdrawalRejected($withdrawal));

            return $withdrawal->fresh();
        });
    }
}
