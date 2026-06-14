<?php

namespace App\Filament\Tenant\Widgets;

use App\Filament\Tenant\Pages\POS;
use App\Filament\Tenant\Resources\ProductResource;
use App\Filament\Tenant\Resources\SellingResource;
use App\Filament\Tenant\Resources\MemberResource;
use Filament\Widgets\Widget;

class QuickActions extends Widget
{
    protected static string $view = 'filament.tenant.widgets.quick-actions';

    protected int | string | array $columnSpan = 'full';

    public function getActions(): array
    {
        return [
            [
                'label' => __('Buka Kasir'),
                'icon' => 'heroicon-o-calculator',
                'url' => POS::getUrl(),
                'color' => 'primary',
            ],
            [
                'label' => __('Lihat Penjualan'),
                'icon' => 'heroicon-o-receipt-percent',
                'url' => SellingResource::getUrl(),
                'color' => 'success',
            ],
            [
                'label' => __('Kelola Produk'),
                'icon' => 'heroicon-o-cube',
                'url' => ProductResource::getUrl(),
                'color' => 'info',
            ],
            [
                'label' => __('Kelola Member'),
                'icon' => 'heroicon-o-users',
                'url' => MemberResource::getUrl(),
                'color' => 'warning',
            ],
        ];
    }
}
