<?php

namespace App\Models\Tenants;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @mixin IdeHelperTable
 */
use App\Models\Traits\HasTenant;
class Table extends Model
{
    use HasTenant;
    use HasFactory, SoftDeletes;

    protected $guarded = ['id'];

    protected $casts = [
        'capacity' => 'integer',
        'sort_order' => 'integer',
    ];

    public function Sellings(): HasMany
    {
        return $this->hasMany(Selling::class);
    }

    public function scopeOpen(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->whereHas('Sellings', function ($q) {
            $q->whereIn('status', ['open', 'partially_paid']);
        });
    }

    public function activeSelling(): ?Selling
    {
        return $this->Sellings()
            ->whereIn('status', ['open', 'partially_paid'])
            ->latest()
            ->first();
    }

    protected static function booted(): void
    {
        static::deleting(function (self $table) {
            if ($table->activeSelling()) {
                throw new \RuntimeException('Cannot delete a table with an active bill');
            }
        });
    }
}
