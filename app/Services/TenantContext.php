<?php

namespace App\Services;

class TenantContext
{
    private static ?string $currentTenantId = null;

    public static function set(?string $tenantId): void
    {
        self::$currentTenantId = $tenantId;
    }

    public static function get(): ?string
    {
        if (self::$currentTenantId) {
            return self::$currentTenantId;
        }

        if ($user = auth()->user()) {
            return $user->tenant_id;
        }

        if ($admin = auth('admin')->user()) {
            return null; // admin users don't have tenant_id
        }

        return session('tenant_id');
    }

    public static function reset(): void
    {
        self::$currentTenantId = null;
    }
}
