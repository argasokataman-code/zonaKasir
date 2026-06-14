<?php

namespace App\Models\Traits;

use App\Services\TenantContext;
use Illuminate\Database\Eloquent\Builder;
use ReflectionClass;

trait HasTenant
{
    protected static function bootHasTenant(): void
    {
        static::addGlobalScope('tenant', function (Builder $builder) {
            $tenantId = TenantContext::get();

            if ($tenantId) {
                $builder->where((new static)->getTable().'.tenant_id', $tenantId);
            }
        });

        static::creating(function ($model) {
            if (! $model->tenant_id) {
                $model->tenant_id = TenantContext::get();
            }
        });
    }

    public function initializeHasTenant(): void
    {
        // Check if model explicitly defines $fillable (not just inherited from Model)
        $reflection = new ReflectionClass($this);
        $property = $reflection->getProperty('fillable');
        $isExplicitlySet = ! $property->isDefault();

        if ($isExplicitlySet) {
            // Model has explicit $fillable — add tenant_id
            if (! in_array('tenant_id', $this->fillable ?? [])) {
                $this->fillable[] = 'tenant_id';
            }
        } elseif (property_exists($this, 'guarded') && ! empty($this->guarded)) {
            // Model has explicit $guarded (e.g., ['id']) — remove tenant_id
            $this->guarded = array_diff($this->guarded, ['tenant_id']);
        }
        // else: model uses default $fillable = [] with $guarded = [] — all fillable, no action needed
    }
}
