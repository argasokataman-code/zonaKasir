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
            return Plan::where('is_active', true)
                ->orderBy('price_monthly')
                ->get()
                ->map(function ($plan, $idx) {
                    $plan->is_popular = $idx === 1;
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
