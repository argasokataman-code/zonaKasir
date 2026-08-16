<?php

namespace App\Models\Tenants;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * @mixin IdeHelperCashDrawer
 */
use App\Models\Traits\HasTenant;
class CashDrawer extends Model
{
    use HasTenant;
    use HasFactory, SoftDeletes, LogsActivity;

    protected $guarded = ['id'];

    protected static $recordEvents = ['created', 'updated', 'deleted'];

    protected $casts = [
        'cash' => 'float',
        'opening_amount' => 'float',
        'closing_amount' => 'float',
        'expected_total' => 'float',
        'actual_total' => 'float',
        'difference' => 'float',
        'closed_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $drawer) {
            $last = static::query()->orderByDesc('id')->value('shift_no');
            $num = $last ? ((int) substr($last, 5) + 1) : 1;
            $drawer->shift_no = 'SHFT-'.str_pad((string) $num, 4, '0', STR_PAD_LEFT);
            $drawer->opening_amount = $drawer->cash;
        });
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults();
    }

    public function openedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'opened_by');
    }

    public function closedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    public function scopeToday()
    {
        return $this->whereDate('created_at', now());
    }

    public function scopeLastOpened()
    {
        return $this->whereNull('closed_by')->latest();
    }
}
