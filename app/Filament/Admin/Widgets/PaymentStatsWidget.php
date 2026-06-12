<?php

namespace App\Filament\Admin\Widgets;

use App\Models\Tenant;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

class PaymentStatsWidget extends BaseWidget
{
    protected static ?string $pollingInterval = '120s';

    protected function getStats(): array
    {
        $totalTenants = Tenant::count();
        $activeTenants = Tenant::where('is_active', true)->count();

        return [
            Stat::make(__('Total Tenants'), $totalTenants)
                ->description(__('All registered tenants'))
                ->color('primary'),
            Stat::make(__('Active Tenants'), $activeTenants)
                ->description(__('Currently active tenants'))
                ->color('success'),
        ];
    }
}
