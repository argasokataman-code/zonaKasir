<?php

namespace App\Models\Tenants;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * @mixin IdeHelperMember
 */
class Member extends Authenticatable
{
    use HasApiTokens, HasFactory, SoftDeletes, LogsActivity, Notifiable;

    protected $guarded = ['id'];

    protected $hidden = ['password', 'remember_token'];

    protected static $recordEvents = ['created', 'updated', 'deleted'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'joined_date' => 'date',
            'password' => 'hashed',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults();
    }

    public function receivables(): HasMany
    {
        return $this->hasMany(Receivable::class);
    }

    public function sellings(): HasMany
    {
        return $this->hasMany(Selling::class);
    }

    public function loyaltyPointLogs(): HasMany
    {
        return $this->hasMany(LoyaltyPointLog::class);
    }

    public function vouchers(): HasMany
    {
        return $this->hasMany(Voucher::class);
    }

    public function walletTransactions(): HasMany
    {
        return $this->hasMany(WalletTransaction::class);
    }

    public function availablePoints(): Attribute
    {
        return Attribute::get(fn () => (int) $this->total_points);
    }
}
