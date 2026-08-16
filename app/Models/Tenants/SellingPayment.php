<?php

namespace App\Models\Tenants;

use App\Models\Traits\HasTenant;
use Illuminate\Database\Eloquent\Model;

class SellingPayment extends Model
{
    use HasTenant;

    protected $guarded = ['id'];

    protected $casts = [
        'amount' => 'float',
        'is_cash' => 'boolean',
        'payment_date' => 'datetime',
    ];

    public function selling()
    {
        return $this->belongsTo(Selling::class);
    }

    public function paymentMethod()
    {
        return $this->belongsTo(PaymentMethod::class);
    }
}
