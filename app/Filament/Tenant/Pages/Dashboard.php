<?php

namespace App\Filament\Tenant\Pages;

use App\Filament\Tenant\Resources\SellingResource\Widgets\SellingOverview;
use App\Filament\Tenant\Widgets\BalanceWidget;
use App\Filament\Tenant\Widgets\InventoryStats;
use App\Filament\Tenant\Widgets\LowStockProducts;
use App\Filament\Tenant\Widgets\PaymentMethodChart;
use App\Filament\Tenant\Widgets\QuickActions;
use App\Filament\Tenant\Widgets\SalesChart;
use App\Filament\Tenant\Widgets\TenantNotifications;
use App\Filament\Tenant\Widgets\TodaysBestSellingProduct;
use App\Filament\Tenant\Widgets\TransactionStats;
use App\Filament\Tenant\Widgets\TrialBanner;
use Filament\Forms\Components\DatePicker;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;

class Dashboard extends BaseDashboard
{
    use HasFiltersForm;

    protected function getHeaderWidgets(): array
    {
        return [
            TrialBanner::class,
            QuickActions::class,
            SellingOverview::class,
            TransactionStats::class,
            BalanceWidget::class,
        ];
    }

    public function getWidgets(): array
    {
        return [
            SalesChart::class,
            PaymentMethodChart::class,
            InventoryStats::class,
            TodaysBestSellingProduct::class,
            LowStockProducts::class,
            TenantNotifications::class,
        ];
    }
}
