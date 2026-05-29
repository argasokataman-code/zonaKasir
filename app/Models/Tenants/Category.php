<?php

namespace App\Models\Tenants;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * @mixin IdeHelperCategory
 */
class Category extends Model
{
    use HasFactory, LogsActivity;

    protected $guarded = [];
    protected static $recordEvents = ['created', 'updated', 'deleted'];

    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'category_id');
    }
}
