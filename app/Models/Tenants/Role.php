<?php

namespace App\Models\Tenants;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Spatie\Permission\Models\Role as ModelsRole;
use Spatie\Permission\PermissionRegistrar;

/**
 * @mixin IdeHelperRole
 */
use App\Models\Traits\HasTenant;
class Role extends ModelsRole
{
    use HasTenant;

    /**
     * Permission groups mapped to Filament navigation structure.
     * Each group contains permission name suffixes that belong to it.
     */
    public const PERMISSION_GROUPS = [
        'kasir' => [
            'label' => 'Sales',
            'icon' => 'heroicon-o-banknotes',
            'web' => [
                'create selling', 'read selling', 'update selling', 'delete selling',
                'set selling method',
                'open cash drawer', 'enable cash drawer', 'close cash drawer',
                'can print selling',
            ],
            'sanctum' => [
                'create selling', 'read selling', 'update selling', 'delete selling',
                'set selling method',
                'open cash drawer', 'enable cash drawer', 'close cash drawer',
            ],
        ],
        'inventory' => [
            'label' => 'Inventory',
            'icon' => 'heroicon-o-cube',
            'web' => [
                'create product', 'read product', 'update product', 'delete product',
                'create product stock', 'read product stock', 'update product stock', 'delete product stock',
                'create category', 'read category', 'update category', 'delete category',
                'create supplier', 'read supplier', 'update supplier', 'delete supplier',
                'create table', 'read table', 'update table', 'delete table',
                'create purchasing', 'read purchasing', 'update purchasing', 'delete purchasing', 'approve purchasing',
                'create stock opname', 'read stock opname', 'update stock opname', 'delete stock opname', 'approve stock opname',
                'import product',
            ],
            'sanctum' => [
                'create product', 'read product', 'update product', 'delete product',
                'create product stock', 'read product stock', 'update product stock', 'delete product stock',
                'create category', 'read category', 'update category', 'delete category',
                'create supplier', 'read supplier', 'update supplier', 'delete supplier',
                'create table', 'read table', 'update table', 'delete table',
                'create purchasing', 'read purchasing', 'update purchasing', 'delete purchasing', 'approve purchasing',
                'create stock opname', 'read stock opname', 'update stock opname', 'delete stock opname', 'approve stock opname',
            ],
        ],
        'pelanggan' => [
            'label' => 'Members',
            'icon' => 'heroicon-o-users',
            'web' => [
                'create member', 'read member', 'update member', 'delete member',
                'read receivable',
                'create receivable payment', 'read receivable payment', 'update receivable payment', 'delete receivable payment',
            ],
            'sanctum' => [
                'create member', 'read member', 'update member', 'delete member',
                'read receivable',
                'create receivable payment', 'read receivable payment', 'update receivable payment', 'delete receivable payment',
            ],
        ],
        'user' => [
            'label' => 'User',
            'icon' => 'heroicon-o-key',
            'web' => [
                'create user', 'read user', 'update user', 'delete user',
                'create role', 'read role', 'update role', 'delete role',
                'read permission',
            ],
            'sanctum' => [],
        ],
        'laporan' => [
            'label' => 'Report',
            'icon' => 'heroicon-o-chart-bar',
            'web' => [
                'generate cashier report',
                'generate selling report',
                'generate product report',
                'read revenue overview',
                'read sales overview',
                'read discount overview',
            ],
            'sanctum' => [
                'generate cashier report',
                'generate selling report',
                'generate product report',
            ],
        ],
        'promo' => [
            'label' => 'General',
            'icon' => 'heroicon-o-ticket',
            'web' => [
                'create voucher', 'read voucher', 'update voucher', 'delete voucher',
                'view settlements',
                'request withdrawal',
                'manage withdrawals',
            ],
            'sanctum' => [
                'create voucher', 'read voucher', 'update voucher', 'delete voucher',
            ],
        ],
        'setting' => [
            'label' => 'Setting',
            'icon' => 'heroicon-o-cog-6-tooth',
            'web' => [
                'access general setting',
                'access printer',
                'access web app',
                'access report',
                'access feature flag',
                'can update app',
                'can restore app',
                'set default tax',
                'read about', 'update about',
                'update currency',
                'read detail initial price',
                'can print label',
                'read profile', 'update profile',
            ],
            'sanctum' => [
                'set default tax',
                'read about', 'update about',
                'update currency',
                'enable secure initial price', 'verify secure initial price',
                'read detail initial price',
                'read profile', 'update profile',
                'access feature flag',
                'can update app',
                'can restore app',
                'set the minimum stock notification',
                'can print label',
            ],
        ],
    ];

    public function mobilePermissions(): BelongsToMany
    {
        return $this->belongsToMany(
            config('permission.models.permission'),
            config('permission.table_names.role_has_permissions'),
            app(PermissionRegistrar::class)->pivotRole,
            app(PermissionRegistrar::class)->pivotPermission
        )->where('guard_name', 'sanctum');
    }

    public function webPermissions(): BelongsToMany
    {
        return $this->belongsToMany(
            config('permission.models.permission'),
            config('permission.table_names.role_has_permissions'),
            app(PermissionRegistrar::class)->pivotRole,
            app(PermissionRegistrar::class)->pivotPermission
        )->where('guard_name', 'web');
    }

    /**
     * Get a BelongsToMany relationship filtered by permission group and guard.
     */
    public function groupedPermissions(string $guard, array $permissionNames): BelongsToMany
    {
        return $this->belongsToMany(
            config('permission.models.permission'),
            config('permission.table_names.role_has_permissions'),
            app(PermissionRegistrar::class)->pivotRole,
            app(PermissionRegistrar::class)->pivotPermission
        )
            ->where('guard_name', $guard)
            ->whereIn(config('permission.table_names.permissions').'.name', $permissionNames);
    }

    // --- Explicit relationship methods for Filament form (property access) ---

    // Kasir
    public function webKasirPermissions(): BelongsToMany
    {
        return $this->groupedPermissions('web', self::PERMISSION_GROUPS['kasir']['web']);
    }

    public function sanctumKasirPermissions(): BelongsToMany
    {
        return $this->groupedPermissions('sanctum', self::PERMISSION_GROUPS['kasir']['sanctum']);
    }

    // Inventory
    public function webInventoryPermissions(): BelongsToMany
    {
        return $this->groupedPermissions('web', self::PERMISSION_GROUPS['inventory']['web']);
    }

    public function sanctumInventoryPermissions(): BelongsToMany
    {
        return $this->groupedPermissions('sanctum', self::PERMISSION_GROUPS['inventory']['sanctum']);
    }

    // Pelanggan
    public function webPelangganPermissions(): BelongsToMany
    {
        return $this->groupedPermissions('web', self::PERMISSION_GROUPS['pelanggan']['web']);
    }

    public function sanctumPelangganPermissions(): BelongsToMany
    {
        return $this->groupedPermissions('sanctum', self::PERMISSION_GROUPS['pelanggan']['sanctum']);
    }

    // User
    public function webUserPermissions(): BelongsToMany
    {
        return $this->groupedPermissions('web', self::PERMISSION_GROUPS['user']['web']);
    }

    // Laporan
    public function webLaporanPermissions(): BelongsToMany
    {
        return $this->groupedPermissions('web', self::PERMISSION_GROUPS['laporan']['web']);
    }

    public function sanctumLaporanPermissions(): BelongsToMany
    {
        return $this->groupedPermissions('sanctum', self::PERMISSION_GROUPS['laporan']['sanctum']);
    }

    // Promo
    public function webPromoPermissions(): BelongsToMany
    {
        return $this->groupedPermissions('web', self::PERMISSION_GROUPS['promo']['web']);
    }

    public function sanctumPromoPermissions(): BelongsToMany
    {
        return $this->groupedPermissions('sanctum', self::PERMISSION_GROUPS['promo']['sanctum']);
    }

    // Setting
    public function webSettingPermissions(): BelongsToMany
    {
        return $this->groupedPermissions('web', self::PERMISSION_GROUPS['setting']['web']);
    }

    public function sanctumSettingPermissions(): BelongsToMany
    {
        return $this->groupedPermissions('sanctum', self::PERMISSION_GROUPS['setting']['sanctum']);
    }
}
