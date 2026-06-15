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
        float $amount,
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

        return DB::transaction(function () use ($amount, $idempotencyKey, $about) {
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

            $autoMax = config('flip.withdrawal_approval.auto_approve_max', 5000000);
            $singleAdminMax = config('flip.withdrawal_approval.single_admin_max', 25000000);
            $tenantAge = $about->created_at->diffInDays(now());

            if ($amount > $singleAdminMax) {
                $status = 'pending';
                $approvedBy = null;
            } elseif ($amount <= $autoMax && $tenantAge >= 30) {
                $status = 'approved';
                $approvedBy = auth()->id();
            } else {
                $status = 'pending';
                $approvedBy = null;
            }

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
                'processed_at'        => $status === 'approved' ? now() : null,
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

            // Pre-flight: Check Flip balance before sending
            $flipBalance = $this->flipData->getBalance();
            if ($flipBalance === null) {
                throw new DisbursementFailedException('Gagal memeriksa saldo Flip');
            }
            if (($flipBalance['balance'] ?? 0) < $withdrawal->amount) {
                throw new DisbursementFailedException(
                    'Saldo Flip tidak mencukupi. Dibutuhkan: Rp ' . number_format($withdrawal->amount, 0, ',', '.')
                    . '. Tersedia: Rp ' . number_format($flipBalance['balance'] ?? 0, 0, ',', '.')
                );
            }

            $result = $this->disbursement->send([
                'bank_code'         => $withdrawal->bank_code,
                'account_number'    => $withdrawal->bank_account_number,
                'account_name'      => $withdrawal->bank_account_name,
                'amount'            => $withdrawal->amount,
                'remark'           => "Zonakasir WD #{$withdrawal->id}",
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
                'processed_at'      => now(),
            ]);

            LedgerEntry::where('reference_type', 'withdrawal_request')
                ->where('reference_id', $withdrawal->id)
                ->update(['reference_type' => 'withdrawal_complete']);

            // Send notification to tenant only if completed
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

            // Send notification to tenant
            Notification::send($withdrawal->requestedBy, new WithdrawalRejected($withdrawal));

            return $withdrawal->fresh();
        });
    }
}
