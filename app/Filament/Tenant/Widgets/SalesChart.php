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
        $startUtc = now($timezone)->subDays(6)->startOfDay()->setTimezone('UTC');
        $endUtc = now($timezone)->addDay()->startOfDay()->setTimezone('UTC');

        // Single grouped query instead of 7 separate queries
        $dailyData = Selling::query()
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('COALESCE(SUM(total_price - tax_price - total_discount_per_item - discount_price - total_cost), 0) as net_revenue'),
                DB::raw('COUNT(*) as total')
            )
            ->isPaid()
            ->whereBetween('created_at', [$startUtc, $endUtc])
            ->groupBy(DB::raw('DATE(created_at)'))
            ->get()
            ->keyBy('date');

        $labels = [];
        $revenue = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now($timezone)->subDays($i)->startOfDay()->format('Y-m-d');
            $labels[] = now($timezone)->subDays($i)->startOfDay()->format('d M');
            $revenue[] = round(($dailyData->get($date)->net_revenue ?? 0) / 1000);
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
