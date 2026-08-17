<?php

namespace App\Models\Tenants;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @mixin IdeHelperSellingDetail
 */
use App\Models\Traits\HasTenant;
class SellingDetail extends Model
{
    use HasTenant;
    use HasFactory, HasTenant;

    protected $guarded = ['id'];

    public const KITCHEN_QUEUE = null;
    public const KITCHEN_IN_PROGRESS = 'in_progress';
    public const KITCHEN_DONE = 'done';

    public function selling()
    {
        return $this->belongsTo(Selling::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function splitGroup()
    {
        return $this->belongsTo(SellingSplitGroup::class, 'split_group_id');
    }

    public function pricePerUnit(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->price / $this->qty,
        );
    }

    public function totalPrice(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->price - $this->discount_price,
        );
    }
}
