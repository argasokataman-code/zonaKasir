<?php

namespace App\Filament\Admin\Widgets;

use App\Models\Invoice;
use App\Models\Subscription;
use App\Tenant;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class BillingStats extends BaseWidget
{
    protected function getStats(): array
    {
        $active = Subscription::where('status', 'active')->count();
        $trialing = Subscription::where('status', 'trialing')->count();
        $expired = Subscription::where('status', 'expired')->count();
        $total = $active + $trialing + $expired;

        $monthlyRevenue = Invoice::where('status', 'paid')
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->sum('amount');

        return [
            Stat::make('Active Subscriptions', $active)
                ->description($total > 0 ? round($active / $total * 100, 1) . '% of total' : 'No data')
                ->descriptionIcon('heroicon-o-check-circle')
                ->color('success'),
            Stat::make('Trialing', $trialing)
                ->description($trialing > 0 ? 'Will expire soon' : 'No trials')
                ->descriptionIcon('heroicon-o-clock')
                ->color('warning'),
            Stat::make('Monthly Revenue', number_format($monthlyRevenue, 0, ',', '.'))
                ->description('Paid invoices this month')
                ->descriptionIcon('heroicon-o-currency-dollar')
                ->color('primary'),
        ];
    }
}
