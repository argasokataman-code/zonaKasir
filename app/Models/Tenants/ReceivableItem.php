<?php

namespace App\Models\Tenants;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @mixin IdeHelperReceivableItem
 */
use App\Models\Traits\HasTenant;
class ReceivableItem extends Model
{
    use HasTenant;
    use HasFactory, HasTenant;

    protected $guarded = ['id'];

    public function receivable(): BelongsTo
    {
        return $this->belongsTo(Receivable::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
