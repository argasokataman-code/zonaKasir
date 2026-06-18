<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\PricingResource;
use App\Models\Plan;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

class PlanController extends Controller
{
    public function index(): JsonResponse
    {
        $plans = Cache::remember('pricing_plans', 3600, function () {
            return Plan::select('id', 'name', 'slug', 'price_monthly', 'price_yearly', 'features', 'max_stores', 'max_users', 'is_active')
                ->where('is_active', true)
                ->orderByRaw("CASE WHEN slug = 'on-premise' THEN 1 ELSE 0 END")
                ->orderBy('price_monthly')
                ->get()
                ->map(function ($plan) {
                    $plan->is_popular = $plan->slug === 'pro';
                    return $plan;
                });
        });

        $data = PricingResource::collection($plans);

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }
}
