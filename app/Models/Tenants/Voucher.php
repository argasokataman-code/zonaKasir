<?php

namespace App\Models\Tenants;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * @mixin IdeHelperVoucher
 */
class Voucher extends Model
{
    use HasFactory, LogsActivity;

    protected $guarded = ['id'];

    protected static $recordEvents = ['created', 'updated', 'deleted'];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'expired' => 'date',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults();
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function scopeGlobal(Builder $builder): Builder
    {
        return $builder->whereNull('member_id');
    }

    public function scopeForMember(Builder $builder, int $memberId): Builder
    {
        return $builder->where('member_id', $memberId);
    }
}
