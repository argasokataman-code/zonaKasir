<?php

namespace App\Filament\Tenant\Pages;

use App\Filament\Tenant\Resources\SellingResource\Widgets\SellingOverview;
use App\Filament\Tenant\Widgets\BalanceWidget;
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
            BalanceWidget::class,
            SellingOverview::class,
        ];
    }
}
