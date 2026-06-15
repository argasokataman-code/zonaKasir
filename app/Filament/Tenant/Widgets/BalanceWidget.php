<?php

namespace App\Filament\Tenant\Widgets;

use App\Services\Tenants\LedgerService;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class BalanceWidget extends BaseWidget
{
    protected static ?string $pollingInterval = '60s';

    protected function getColumns(): int
    {
        return 1;
    }

    protected function getStats(): array
    {
        $ledger = app(LedgerService::class);
        $balance = $ledger->getCurrentBalance();

        return [
            Stat::make(__('Available Balance'), 'Rp ' . number_format($balance, 0, ',', '.'))
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('success'),
        ];
    }
}
