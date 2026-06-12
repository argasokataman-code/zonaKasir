<?php

namespace App\Filament\Tenant\Resources\WithdrawalResource\Widgets;

use App\Models\Tenants\MidtransPayment;
use App\Models\Tenants\Withdrawal;
use App\Services\Tenants\LedgerService;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Number;

class BalanceOverview extends BaseWidget
{
    protected static ?string $pollingInterval = '60s';

    protected function getStats(): array
    {
        $ledgerService = app(LedgerService::class);
        $balance = $ledgerService->getCurrentBalance();
        $pendingWithdrawals = Withdrawal::where('status', 'pending')->sum('amount');
        $totalSettled = MidtransPayment::where('status', 'settlement')->sum('gross_amount');

        return [
            Stat::make(__('Available Balance'), 'Rp ' . number_format($balance, 0, ',', '.'))
                ->description(__('Ready for withdrawal'))
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('success'),
            Stat::make(__('Pending Withdrawals'), 'Rp ' . number_format($pendingWithdrawals, 0, ',', '.'))
                ->description(__('Awaiting approval'))
                ->descriptionIcon('heroicon-m-clock')
                ->color('warning'),
            Stat::make(__('Total Settled'), 'Rp ' . number_format($totalSettled, 0, ',', '.'))
                ->description(__('All time settled transactions'))
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('info'),
        ];
    }
}
