<?php

namespace App\Services;

use App\Models\Coupon;
use App\Models\Subscription;
use Illuminate\Support\Facades\DB;
use Stancl\Tenancy\Facades\Tenancy;
use Exception;

class CouponService
{
    public function redeem(string $code, string|int $tenantId): array
    {
        return DB::transaction(function () use ($code, $tenantId) {
            $coupon = Tenancy::central(function () use ($code) {
                return Coupon::where('code', $code)->lockForUpdate()->first();
            });

            if (! $coupon) {
                throw new Exception('Kode kupon tidak ditemukan');
            }

            if (! $coupon->isValid()) {
                throw new Exception('Kupon sudah tidak valid atau kedaluwarsa');
            }

            if ($coupon->type === 'trial_extension') {
                return $this->applyTrialExtension($coupon, $tenantId);
            }

            Tenancy::central(function () use ($coupon) {
                $coupon->increment('used_count');
            });

            return [
                'success' => true,
                'type' => $coupon->type,
                'value' => $coupon->value,
                'message' => 'Kupon berhasil digunakan',
            ];
        });
    }

    private function applyTrialExtension(Coupon $coupon, string|int $tenantId): array
    {
        $subscription = Tenancy::central(function () use ($tenantId) {
            return Subscription::where('tenant_id', $tenantId)
                ->whereIn('status', ['trialing', 'active'])
                ->latest()
                ->first();
        });

        if (! $subscription) {
            throw new Exception('Tidak ada langganan aktif untuk tenant ini');
        }

        $extraDays = $coupon->trial_days ?? 0;

        if ($subscription->status === 'trialing' && $subscription->trial_ends_at) {
            $subscription->update([
                'trial_ends_at' => $subscription->trial_ends_at->addDays($extraDays),
            ]);
        } elseif ($subscription->status === 'active' && $subscription->ends_at) {
            $subscription->update([
                'ends_at' => $subscription->ends_at->addDays($extraDays),
            ]);
        }

        Tenancy::central(function () use ($coupon) {
            $coupon->increment('used_count');
        });

        return [
            'success' => true,
            'type' => 'trial_extension',
            'value' => $extraDays,
            'message' => "Trial diperpanjang {$extraDays} hari",
        ];
    }
}