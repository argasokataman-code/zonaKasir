<?php

namespace App\Filament\Admin\Widgets;

use App\Tenant;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class DashboardStats extends BaseWidget
{
    protected function getStats(): array
    {
        $total = Tenant::count();
        $thisMonth = Tenant::whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();
        $lastMonth = Tenant::whereMonth('created_at', now()->subMonth()->month)
            ->whereYear('created_at', now()->subMonth()->year)
            ->count();
        $trend = $lastMonth > 0 ? round(($thisMonth - $lastMonth) / $lastMonth * 100) : 0;

        $latest = Tenant::latest()->take(5)->get();

        return [
            Stat::make('Total Tenants', number_format($total))
                ->description("{$thisMonth} new this month")
                ->descriptionIcon('heroicon-o-building-storefront')
                ->color('primary'),
            Stat::make('This Month', number_format($thisMonth))
                ->description($trend >= 0 ? "↑ {$trend}% from last month" : "↓ {$trend}% from last month")
                ->descriptionIcon($trend >= 0 ? 'heroicon-o-arrow-trending-up' : 'heroicon-o-arrow-trending-down')
                ->color($trend >= 0 ? 'success' : 'danger'),
            Stat::make('Latest Tenant', $latest->first()?->data['full_name'] ?? 'N/A')
                ->description($latest->first()?->created_at?->diffForHumans() ?? '')
                ->descriptionIcon('heroicon-o-user-plus'),
        ];
    }
}
