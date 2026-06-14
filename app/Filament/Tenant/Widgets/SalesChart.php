<?php

namespace App\Filament\Tenant\Widgets;

use App\Models\Tenants\Profile;
use App\Models\Tenants\Selling;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class SalesChart extends ChartWidget
{
    protected static ?string $heading = 'Revenue (7 Hari)';

    protected static ?string $maxHeight = '300px';

    protected static ?string $pollingInterval = '120s';

    protected function getType(): string
    {
        return 'line';
    }

    protected function getData(): array
    {
        $timezone = Profile::get()->timezone ?? 'UTC';
        $labels = [];
        $revenue = [];
        $transactions = [];

        for ($i = 6; $i >= 0; $i--) {
            $date = now($timezone)->subDays($i)->startOfDay();
            $labels[] = $date->format('d M');

            $startUtc = $date->copy()->setTimezone('UTC');
            $endUtc = $startUtc->copy()->addDay();

            $selling = Selling::query()
                ->select(
                    DB::raw('COALESCE(SUM(total_price - tax_price - total_discount_per_item - discount_price - total_cost), 0) as net_revenue'),
                    DB::raw('COUNT(*) as total')
                )
                ->isPaid()
                ->whereBetween('created_at', [$startUtc, $endUtc])
                ->first();

            $revenue[] = round($selling->net_revenue / 1000);
            $transactions[] = $selling->total;
        }

        return [
            'datasets' => [
                [
                    'label' => __('Revenue (Rp x1000)'),
                    'data' => $revenue,
                    'borderColor' => '#FF6600',
                    'backgroundColor' => 'rgba(255, 102, 0, 0.1)',
                    'fill' => true,
                    'tension' => 0.4,
                ],
            ],
            'labels' => $labels,
        ];
    }
}
