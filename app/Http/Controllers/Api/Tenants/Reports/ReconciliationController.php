<?php

namespace App\Http\Controllers\Api\Tenants\Reports;

use App\Http\Controllers\Controller;
use App\Models\Tenants\MidtransPayment;
use App\Models\Tenants\Withdrawal;
use App\Services\Tenants\LedgerService;
use Illuminate\Http\JsonResponse;

class ReconciliationController extends Controller
{
    public function run(): JsonResponse
    {
        $ledger = app(LedgerService::class);
        $yesterday = now()->subDay()->startOfDay();

        $ledgerBalance = $ledger->getCurrentBalance();
        $calculated = MidtransPayment::where('status', 'settlement')
            ->where('paid_at', '<=', $yesterday->copy()->endOfDay())
            ->sum('net_amount');
        $withdrawn = Withdrawal::where('status', 'completed')
            ->where('created_at', '<=', $yesterday->copy()->endOfDay())
            ->sum('amount');
        $expected = $calculated - $withdrawn;
        $diff = $ledgerBalance - $expected;

        return $this->buildResponse()
            ->setData([
                'ledger_balance' => $ledgerBalance,
                'expected_balance' => $expected,
                'diff' => $diff,
                'status' => abs($diff) <= 1 ? 'balanced' : 'mismatch',
            ])
            ->setMessage(abs($diff) <= 1 ? 'Ledger is balanced' : 'Ledger mismatch detected')
            ->present();
    }
}
