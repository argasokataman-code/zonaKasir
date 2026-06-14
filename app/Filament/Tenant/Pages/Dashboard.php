<?php

namespace App\Filament\Tenant\Pages;

use App\Filament\Tenant\Resources\SellingResource\Widgets\SellingOverview;
use App\Filament\Tenant\Widgets\BalanceWidget;
use App\Filament\Tenant\Widgets\TrialBanner;
use Filament\Forms\Components\DatePicker;
use Filament\Pages\Dashboard\Actions\FilterAction;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;

class Dashboard extends BaseDashboard
{
    use HasFiltersForm;

    protected function getHeaderWidgets(): array
    {
        return [
            TrialBanner::class,
            BalanceWidget::class,
            SellingOverview::class,
        ];
    }
}
