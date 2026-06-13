<?php

namespace App\Filament\Admin\Widgets;

use App\Models\Tenants\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class DashboardStats extends BaseWidget
{
    protected function getStats(): array
    {
        $total = User::distinct('tenant_id')->count('tenant_id');
        $thisMonth = User::whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->distinct('tenant_id')
            ->count('tenant_id');
        $lastMonth = User::whereMonth('created_at', now()->subMonth()->month)
            ->whereYear('created_at', now()->subMonth()->year)
            ->distinct('tenant_id')
            ->count('tenant_id');
        $trend = $lastMonth > 0 ? round(($thisMonth - $lastMonth) / $lastMonth * 100) : 0;

        $latestTenantId = User::distinct('tenant_id')
            ->latest()
            ->value('tenant_id');

        return [
            Stat::make('Total Tenants', number_format($total))
                ->description("{$thisMonth} new this month")
                ->descriptionIcon('heroicon-o-building-storefront')
                ->color('primary'),
            Stat::make('This Month', number_format($thisMonth))
                ->description($trend >= 0 ? "↑ {$trend}% from last month" : "↓ {$trend}% from last month")
                ->descriptionIcon($trend >= 0 ? 'heroicon-o-arrow-trending-up' : 'heroicon-o-arrow-trending-down')
                ->color($trend >= 0 ? 'success' : 'danger'),
            Stat::make('Latest Tenant', $latestTenantId ?? 'N/A')
                ->description('Most recent registration')
                ->descriptionIcon('heroicon-o-user-plus'),
        ];
    }
}
