<?php

namespace App\Services;

use App\Models\License;

class LicenseService
{
    public function createTrial(string $tenantId): License
    {
        // If a trial already exists and is active, return it
        $existing = License::where('tenant_id', $tenantId)
            ->where('plan', 'trial')
            ->where('status', 'active')
            ->where('expires_at', '>', now())
            ->first();

        if ($existing) {
            return $existing;
        }

        return License::create([
            'key' => License::generateKey(),
            'plan' => 'trial',
            'status' => 'active',
            'tenant_id' => $tenantId,
            'activated_at' => now(),
            'expires_at' => now()->addDays(14),
        ]);
    }

    public function create(string $tenantId, string $plan, int $days = 365): License
    {
        // Revoke any existing active licenses for this tenant
        License::where('tenant_id', $tenantId)
            ->where('status', 'active')
            ->update(['status' => 'revoked']);

        return License::create([
            'key' => License::generateKey(),
            'plan' => $plan,
            'status' => 'active',
            'tenant_id' => $tenantId,
            'activated_at' => now(),
            'expires_at' => now()->addDays($days),
        ]);
    }

    public function validateAndActivate(string $key, string $tenantId): ?License
    {
        $license = License::where('key', $key)
            ->where('status', 'active')
            ->first();

        if (! $license) {
            return null;
        }

        // If the license is assigned to a different tenant
        if ($license->tenant_id && $license->tenant_id !== $tenantId) {
            return null;
        }

        $license->update([
            'tenant_id' => $tenantId,
            'activated_at' => now(),
            'expires_at' => now()->addYear(),
        ]);

        return $license;
    }

    public function hasValidLicense(string $tenantId): bool
    {
        return License::where('tenant_id', $tenantId)
            ->where('status', 'active')
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->exists();
    }

    public function getActiveLicense(string $tenantId): ?License
    {
        return License::where('tenant_id', $tenantId)
            ->where('status', 'active')
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->first();
    }
}
