<?php

namespace App\Features\Traits;

use App\Services\PlanAccessService;
use Illuminate\Support\Str;

trait ResolvesFromPlan
{
    public function resolve(mixed $scope): mixed
    {
        $tenantId = $scope;

        if (! $tenantId) {
            return false;
        }

        $slug = Str::snake(class_basename(static::class));

        if (! config('plans.features.' . $slug)) {
            return true;
        }

        return app(PlanAccessService::class)->hasFeature($tenantId, $slug);
    }
}
