<?php

namespace App\Http\Middleware;

use App\Models\Tenants\About;
use App\Models\Tenants\LedgerEntry;
use App\Models\Tenants\Withdrawal;
use Closure;
use Illuminate\Http\Request;

class AutoApproveWithdrawal
{
    /**
     * Auto-approve small withdrawals (< 5jt) for trusted tenants.
     */
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        if ($request->route()->named('withdrawal.store') && $response->status() === 201) {
            // The withdrawal was just created; auto-approve if eligible
            $withdrawalId = $response->getData()->data?->id ?? null;

            if ($withdrawalId) {
                $this->autoApproveIfEligible($withdrawalId);
            }
        }

        return $response;
    }

    private function autoApproveIfEligible(int $withdrawalId): void
    {
        $withdrawal = Withdrawal::find($withdrawalId);
        if (!$withdrawal || $withdrawal->status !== 'pending') {
            return;
        }

        $about = About::first();
        $autoMax = config('midtrans.withdrawal_approval.auto_approve_max', 5000000);
        $tenantAge = $about->created_at->diffInDays(now());

        // Auto-approve only if: amount <= 5jt AND tenant is at least 30 days old
        if ($withdrawal->amount <= $autoMax && $tenantAge >= 30) {
            $withdrawal->update([
                'status' => 'approved',
                'approved_by' => $withdrawal->requested_by, // self-approved by system
                'processed_at' => now(),
            ]);
        }
    }
}
