<?php

namespace App\Http\Controllers\Api\Tenants\Reports;

use App\Http\Controllers\Controller;
use App\Models\Tenants\Settlement;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SettlementController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $settlements = Settlement::query()
            ->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 15));

        return $this->buildResponse()
            ->setData($settlements)
            ->setMessage('success get settlements')
            ->present();
    }
}
