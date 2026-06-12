<?php

namespace App\Services\Tenants;

use App\Models\Tenants\MidtransPayment;
use App\Models\Tenants\Withdrawal;
use App\Models\Tenants\Settlement;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ReconciliationService
{
    public function __construct(
        private readonly LedgerService $ledger,
    ) {}

    /**
     * Daily reconciliation for this tenant.
     */
    public function daily(): int
    {
        $mismatches = 0;
        $yesterday = now()->subDay()->startOfDay();

        // 1. Fetch Midtrans transactions (paginated)
        $midtransTxs = $this->fetchMidtransTransactions($yesterday);

        // 2. Cross-validate against DB
        $ourTxs = MidtransPayment::whereDate('paid_at', $yesterday)
            ->where('status', 'settlement')
            ->get();

        foreach ($midtransTxs as $mtx) {
            $our = $ourTxs->firstWhere('order_id', $mtx['order_id']);

            if (!$our) {
                Log::warning('Reconciliation: missing in DB', [
                    'order_id' => $mtx['order_id'],
                    'midtrans_gross' => $mtx['gross_amount'],
                ]);
                $mismatches++;
                continue;
            }

            if ((float) $mtx['gross_amount'] !== (float) $our->gross_amount) {
                Log::warning('Reconciliation: amount mismatch', [
                    'order_id' => $mtx['order_id'],
                    'midtrans' => $mtx['gross_amount'],
                    'our' => $our->gross_amount,
                ]);
                $mismatches++;
            }
        }

        // 3. Balance invariant check
        $ledgerBalance = $this->ledger->getCurrentBalance();
        $calculatedBalance = MidtransPayment::where('status', 'settlement')
            ->where('paid_at', '<=', $yesterday->copy()->endOfDay())
            ->sum('net_amount');
        $withdrawn = Withdrawal::where('status', 'completed')
            ->where('created_at', '<=', $yesterday->copy()->endOfDay())
            ->sum('amount');
        $expected = $calculatedBalance - $withdrawn;

        if (abs($ledgerBalance - $expected) > 1) {
            Log::critical('Reconciliation: balance mismatch', [
                'ledger' => $ledgerBalance,
                'expected' => $expected,
                'diff' => $ledgerBalance - $expected,
            ]);
            $mismatches++;
        }

        // 4. Generate settlement report
        if ($mismatches === 0) {
            $this->generateSettlement($yesterday);
        }

        return $mismatches;
    }

    /**
     * Generate settlement report for a date range.
     */
    private function generateSettlement(\Carbon\Carbon $date): void
    {
        $stats = MidtransPayment::whereDate('paid_at', $date)
            ->where('status', 'settlement')
            ->selectRaw('
                COUNT(*) as count,
                SUM(gross_amount) as total_gross,
                SUM(fee_midtrans) as total_fee_midtrans,
                SUM(fee_platform) as total_fee_platform,
                SUM(net_amount) as total_net
            ')
            ->first();

        if (!$stats || $stats->count == 0) {
            return; // no transactions
        }

        Settlement::create([
            'period_start'      => $date->startOfDay(),
            'period_end'        => $date->copy()->endOfDay(),
            'total_gross'       => $stats->total_gross,
            'total_fee_midtrans' => $stats->total_fee_midtrans,
            'total_fee_platform' => $stats->total_fee_platform,
            'total_net'         => $stats->total_net,
            'transaction_count' => $stats->count,
            'status'            => 'pending',
        ]);
    }

    private function fetchMidtransTransactions(\Carbon\Carbon $date): array
    {
        $about = \App\Models\Tenants\About::first();
        $all = [];
        $token = null;

        do {
            $params = [
                'from'  => $date->timestamp,
                'to'    => $date->copy()->endOfDay()->timestamp,
                'limit' => 200,
            ];
            if ($token) {
                $params['next_page_token'] = $token;
            }

            $res = Http::withBasicAuth($about->midtrans_server_key ?? config('midtrans.server_key'), '')
                ->get('https://api.sandbox.midtrans.com/v2/' . ($about->midtrans_merchant_id ?? config('midtrans.merchant_id')) . '/transaction_status', $params);

            if ($res->failed()) break;

            $data = $res->json();
            $all = array_merge($all, $data['data'] ?? []);
            $token = $data['next_page_token'] ?? null;
        } while ($token);

        return $all;
    }
}
