<?php

namespace App\Models\Tenants;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

/**
 * @mixin IdeHelperMidtransPayment
 */
use App\Models\Traits\HasTenant;
class MidtransPayment extends Model
{
    use HasTenant;
    use HasFactory, LogsActivity;

    protected $guarded = ['id'];

    protected $casts = [
        'cart_data' => 'json',
        'paid_at' => 'datetime',
    ];

    protected static $recordEvents = ['created', 'updated'];

    public function selling(): BelongsTo
    {
        return $this->belongsTo(Selling::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults();
    }
}
