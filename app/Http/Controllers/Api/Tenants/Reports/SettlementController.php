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
            ->select('id', 'period_start', 'period_end', 'total_gross', 'total_fee_midtrans', 'total_fee_platform', 'total_net', 'transaction_count', 'status', 'created_at')
            ->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 15));

        return $this->buildResponse()
            ->setData($settlements)
            ->setMessage('success get settlements')
            ->present();
    }
}
