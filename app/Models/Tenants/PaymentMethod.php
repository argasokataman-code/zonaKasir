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
use App\Models\Traits\HasTenant;
class PaymentMethod extends Model
{
    use HasTenant;
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
        'is_active' => 'boolean',
    ];

    public const MIDTRANS_TYPES = [
        'credit_card', 'debit_card', 'gopay', 'shopeepay', 'qris',
        'bank_transfer', 'indomaret', 'alfamart', 'kredivo', 'akulaku',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $method) {
            $method->setFlagsFromPaymentType();
        });

        static::updating(function (self $method) {
            $method->setFlagsFromPaymentType();
        });
    }

    private function setFlagsFromPaymentType(): void
    {
        if (! $this->payment_type) {
            return;
        }

        $this->is_cash = in_array($this->payment_type, ['cash']);
        $this->is_debit = in_array($this->payment_type, ['debit_card', 'bank_transfer']);
        $this->is_credit = in_array($this->payment_type, ['credit_card', 'kredivo', 'akulaku', 'credit']);
        $this->is_wallet = in_array($this->payment_type, ['gopay', 'shopeepay', 'qris']);
    }

    public function icon(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => config('app.url').'/'.$value,
        );
    }

    public function isMidtrans(): bool
    {
        return in_array($this->payment_type, self::MIDTRANS_TYPES);
    }

    public function midtransType(): ?string
    {
        return $this->isMidtrans() ? $this->payment_type : null;
    }
}
