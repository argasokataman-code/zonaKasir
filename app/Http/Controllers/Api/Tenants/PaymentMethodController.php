<?php

namespace App\Http\Controllers\Api\Tenants;

use App\Http\Controllers\Controller;
use App\Models\Tenants\PaymentMethod;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\QueryBuilder;

class PaymentMethodController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $paymentMethods = QueryBuilder::for(PaymentMethod::class)
            ->orderByDesc('created_at')
            ->simplePaginate($this->resolvePerPage($request) ?? 15);

        return $this->buildResponse()
            ->setData($paymentMethods)
            ->setMessage('Payment methods retrieved successfully')
            ->present();
    }
}
