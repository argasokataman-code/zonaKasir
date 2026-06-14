<?php

namespace App\Filament\Tenant\Widgets;

use App\Models\Tenants\Product;
use App\Models\Tenants\Stock;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class InventoryStats extends BaseWidget
{
    protected static ?string $pollingInterval = '300s';

    protected function getStats(): array
    {
        $totalProducts = Product::count();

        $outOfStock = Product::whereDoesntHave('stocks', function ($query) {
            $query->where('type', 'in')
                ->where('stock', '>', 0)
                ->where('date', '<=', now());
        })->count();

        $lowStock = Product::whereHas('stocks', function ($query) {
            $query->where('type', 'in')
                ->where('stock', '>', 0)
                ->where('stock', '<=', 5)
                ->where('date', '<=', now());
        })->count();

        $expiredSoon = 0;
        try {
            $expiredSoon = Product::nearestExpiredProduct()->count();
        } catch (\Throwable) {
        }

        return [
            Stat::make(__('Total Produk'), $totalProducts)
                ->descriptionIcon('heroicon-m-cube')
                ->color('primary'),
            Stat::make(__('Stok Habis'), $outOfStock)
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color($outOfStock > 0 ? 'danger' : 'success'),
            Stat::make(__('Stok Menipis'), $lowStock)
                ->descriptionIcon('heroicon-m-arrow-down')
                ->color($lowStock > 0 ? 'warning' : 'success'),
            Stat::make(__('Mendekati Expired'), $expiredSoon)
                ->descriptionIcon('heroicon-m-clock')
                ->color($expiredSoon > 0 ? 'danger' : 'success'),
        ];
    }
}
