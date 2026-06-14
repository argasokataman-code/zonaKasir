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
        return self::$currentTenantId;
    }

    public static function reset(): void
    {
        self::$currentTenantId = null;
    }
}
