<?php

namespace App\Models\Traits;

use App\Services\TenantContext;

trait HasTenant
{
    protected static function bootHasTenant(): void
    {
        static::creating(function ($model) {
            if (! $model->tenant_id) {
                $model->tenant_id = TenantContext::get();
            }
        });
    }

    public function initializeHasTenant(): void
    {
        if (property_exists($this, 'guarded') && ! empty($this->guarded)) {
            $this->guarded = array_diff($this->guarded, ['tenant_id']);
            return;
        }

        if (! in_array('tenant_id', $this->fillable ?? [])) {
            $this->fillable[] = 'tenant_id';
        }
    }
}
