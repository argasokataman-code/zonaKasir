<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int    $id
 * @property string $key
 * @property string $plan       'trial', 'starter', 'business'
 * @property string $status     'active', 'expired', 'revoked'
 * @property string $tenant_id
 */
class License extends Model
{
    /** Always use the central database — never a tenant database. */
    protected $connection = 'mysql';

    protected $guarded = ['id'];

    protected $casts = [
        'expires_at' => 'datetime',
        'activated_at' => 'datetime',
    ];

    public function isValid(): bool
    {
        if ($this->status !== 'active') {
            return false;
        }

        if ($this->expires_at && $this->expires_at->isPast()) {
            return false;
        }

        return true;
    }

    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    public function daysLeft(): int
    {
        if (! $this->expires_at) {
            return 0;
        }

        return max(0, (int) now()->diffInDays($this->expires_at, absolute: false));
    }

    public static function generateKey(): string
    {
        return 'ZK-'.strtoupper(substr(bin2hex(random_bytes(4)), 0, 8));
    }
}
