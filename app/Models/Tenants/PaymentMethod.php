<?php

namespace App\Models\Tenants;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * @mixin IdeHelperPaymentMethod
 */
class PaymentMethod extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $guarded = ['id'];

    protected static $recordEvents = ['created', 'updated', 'deleted'];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults();
    }

    protected $casts = [
        'is_cash' => 'boolean',
        'is_debit' => 'boolean',
        'is_credit' => 'boolean',
        'is_wallet' => 'boolean',
    ];

    public const MIDTRAMS_TYPES = [
        'credit_card', 'debit_card', 'gopay', 'shopeepay', 'qris',
        'bank_transfer', 'indomaret', 'alfamart', 'kredivo', 'akulaku',
    ];

    public function icon(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => config('app.url').'/'.$value,
        );
    }

    public function isMidtrans(): bool
    {
        return in_array($this->payment_type, self::MIDTRAMS_TYPES);
    }

    public function midtransType(): ?string
    {
        return $this->isMidtrans() ? $this->payment_type : null;
    }
}
