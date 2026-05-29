<?php

namespace App\Models\Tenants;

use App\Traits\UseTimezoneAwareQuery;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * @mixin IdeHelperSelling
 */
class Selling extends Model
{
    use HasFactory, UseTimezoneAwareQuery, LogsActivity;

    protected $guarded = ['friend_price'];
    protected static $recordEvents = ['created', 'updated', 'deleted'];

    protected $appends = [
        'grand_total_price',
    ];

    public function sellingDetails()
    {
        return $this->hasMany(SellingDetail::class);
    }

    public function paymentMethod()
    {
        return $this->belongsTo(PaymentMethod::class);
    }

    public function member()
    {
        return $this->belongsTo(Member::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function cashDrawer()
    {
        return $this->belongsTo(CashDrawer::class);
    }

    public function scopeIsPaid(Builder $builder): Builder
    {
        return $builder->where('is_paid', true);
    }

    public function scopeIsNotPaid(Builder $builder): Builder
    {
        return $builder->where('is_paid', false);
    }

    public function grandTotalPrice(): Attribute
    {
        return Attribute::make(get: fn () => $this->total_price - $this->tax_price - $this->total_discount_per_item - $this->discount_price);
    }

    public function table(): BelongsTo
    {
        return $this->belongsTo(Table::class);
    }

    public function getActivitylogOptions(): \Spatie\Activitylog\Contracts\Activity
    {
        return \Spatie\Activitylog\ActivityLogger::withProperties(['user_id' => auth()->id()])->useLog('default');
    }
