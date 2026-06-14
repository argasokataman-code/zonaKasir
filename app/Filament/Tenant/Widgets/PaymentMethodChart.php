<?php

namespace App\Filament\Tenant\Widgets;

use App\Models\Tenants\Profile;
use App\Models\Tenants\Selling;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class PaymentMethodChart extends ChartWidget
{
    protected static ?string $heading = 'Metode Pembayaran (Hari Ini)';

    protected static ?string $maxHeight = '300px';

    protected static ?string $pollingInterval = '120s';

    protected function getType(): string
    {
        return 'doughnut';
    }

    protected function getData(): array
    {
        $timezone = Profile::get()->timezone ?? 'UTC';
        $startOfDay = now($timezone)->startOfDay()->setTimezone('UTC');
        $endOfDay = $startOfDay->copy()->addDay();

        $data = Selling::query()
            ->select(
                'payment_methods.name as method_name',
                DB::raw('COUNT(*) as total'),
                DB::raw('SUM(sellings.total_price - sellings.tax_price - sellings.total_discount_per_item - sellings.discount_price) as total_revenue')
            )
            ->join('payment_methods', 'sellings.payment_method_id', '=', 'payment_methods.id')
            ->isPaid()
            ->whereBetween('sellings.created_at', [$startOfDay, $endOfDay])
            ->groupBy('payment_methods.name')
            ->orderByDesc('total_revenue')
            ->get();

        $colors = ['#FF6600', '#4F46E5', '#10B981', '#F59E0B', '#EF4444', '#8B5CF6', '#EC4899', '#06B6D4'];

        return [
            'datasets' => [
                [
                    'data' => $data->pluck('total_revenue')->toArray(),
                    'backgroundColor' => array_slice($colors, 0, $data->count()),
                ],
            ],
            'labels' => $data->pluck('method_name')->toArray(),
        ];
    }
}
