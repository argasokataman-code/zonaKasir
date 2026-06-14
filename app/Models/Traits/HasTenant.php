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
        if (! in_array('tenant_id', $this->fillable ?? [])) {
            $this->fillable[] = 'tenant_id';
        }
    }
}
