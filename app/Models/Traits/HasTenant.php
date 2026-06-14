<?php

namespace App\Models\Traits;

use App\Services\TenantContext;
use Illuminate\Database\Eloquent\Builder;

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
        // If model uses $guarded (all attributes mass-assignable except guarded ones),
        // just remove tenant_id from the guarded list.
        if (property_exists($this, 'guarded') && ! empty($this->guarded)) {
            $this->guarded = array_diff($this->guarded, ['tenant_id']);
            return;
        }

        // If model uses $fillable (only listed attributes mass-assignable),
        // add tenant_id to the fillable list.
        if (! in_array('tenant_id', $this->fillable ?? [])) {
            $this->fillable[] = 'tenant_id';
        }
    }
}
