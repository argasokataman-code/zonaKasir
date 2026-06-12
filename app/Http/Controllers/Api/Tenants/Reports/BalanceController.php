<?php

namespace App\Http\Controllers\Api\Tenants\Reports;

use App\Http\Controllers\Controller;
use App\Services\Tenants\LedgerService;
use Illuminate\Http\JsonResponse;

class BalanceController extends Controller
{
    public function index(): JsonResponse
    {
        $ledger = app(LedgerService::class);

        return $this->buildResponse()
            ->setData([
                'current_balance' => $ledger->getCurrentBalance(),
                'max_withdrawal' => $ledger->getCurrentBalance() * 0.95,
            ])
            ->setMessage('success get balance')
            ->present();
    }
}
