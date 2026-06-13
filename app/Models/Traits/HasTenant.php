<?php

namespace App\Models\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

trait HasTenant
{
    protected static function bootHasTenant(): void
    {
        static::addGlobalScope('tenant', function (Builder $builder) {
            $tenantId = Auth::user()?->tenant_id
                ?? session('tenant_id')
                ?? request()->header('X-Tenant-ID');

            if ($tenantId) {
                $builder->where((new static)->getTable().'.tenant_id', $tenantId);
            }
        });

        static::creating(function ($model) {
            if (! $model->tenant_id) {
                $model->tenant_id = Auth::user()?->tenant_id
                    ?? session('tenant_id');
            }
        });
    }

    public function initializeHasTenant(): void
    {
        if (! in_array('tenant_id', $this->fillable ?? [])) {
            $this->guarded = array_diff($this->guarded ?? ['id'], ['tenant_id']);
        }
    }
}
