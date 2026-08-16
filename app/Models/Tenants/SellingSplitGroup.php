<?php

namespace App\Models\Tenants;

use App\Models\Traits\HasTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @mixin IdeHelperSellingSplitGroup
 */
class SellingSplitGroup extends Model
{
    use HasTenant, HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'subtotal' => 'float',
        'tax_amount' => 'float',
        'discount_amount' => 'float',
        'total' => 'float',
        'paid_total' => 'float',
    ];

    public function selling(): BelongsTo
    {
        return $this->belongsTo(Selling::class);
    }

    public function details(): HasMany
    {
        return $this->hasMany(SellingDetail::class, 'split_group_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(SellingPayment::class, 'split_group_id');
    }
}
