<?php

namespace App\Console\Commands;

use App\Models\Tenants\MidtransPayment;
use App\Models\Tenants\Settlement;
use Illuminate\Console\Command;

class PaymentsGenerateSettlements extends Command
{
    protected $signature = 'payments:generate-settlements {--date= : Date to generate (Y-m-d)}';

    protected $description = 'Generate settlement reports for tenants';

    public function handle(): int
    {
        $date = $this->option('date')
            ? \Carbon\Carbon::parse($this->option('date'))
            : now()->subDay();

        $stats = MidtransPayment::whereDate('paid_at', $date)
            ->where('status', 'settlement')
            ->selectRaw('
                COUNT(*) as count,
                COALESCE(SUM(gross_amount), 0) as total_gross,
                COALESCE(SUM(fee_midtrans), 0) as total_fee_midtrans,
                COALESCE(SUM(fee_platform), 0) as total_fee_platform,
                COALESCE(SUM(net_amount), 0) as total_net
            ')
            ->first();

        if (!$stats || $stats->count == 0) {
            $this->info("No settlements found for {$date->format('Y-m-d')}");
            return self::SUCCESS;
        }

        Settlement::create([
            'period_start' => $date->copy()->startOfDay(),
            'period_end' => $date->copy()->endOfDay(),
            'total_gross' => $stats->total_gross,
            'total_fee_midtrans' => $stats->total_fee_midtrans,
            'total_fee_platform' => $stats->total_fee_platform,
            'total_net' => $stats->total_net,
            'transaction_count' => $stats->count,
            'status' => 'pending',
        ]);

        $this->info("Settlement generated: {$stats->count} transactions, net Rp " . number_format($stats->total_net, 0, ',', '.'));
        return self::SUCCESS;
    }
}
