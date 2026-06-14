<?php

namespace App\Filament\Tenant\Widgets;

use App\Models\Tenants\Member;
use App\Models\Tenants\Profile;
use App\Models\Tenants\Selling;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Number;

class TransactionStats extends BaseWidget
{
    protected static ?string $pollingInterval = '120s';

    protected function getStats(): array
    {
        $timezone = Profile::get()->timezone ?? 'UTC';
        $startOfDay = now($timezone)->startOfDay()->setTimezone('UTC');
        $endOfDay = $startOfDay->copy()->addDay();
        $startOfYesterday = $startOfDay->copy()->subDay();

        $today = Selling::query()
            ->select(
                DB::raw('COUNT(*) as total_transactions'),
                DB::raw('COALESCE(AVG(total_price - tax_price - total_discount_per_item - discount_price), 0) as avg_transaction'),
                DB::raw('COALESCE(SUM(total_qty), 0) as total_items'),
            )
            ->isPaid()
            ->whereBetween('created_at', [$startOfDay, $endOfDay])
            ->first();

        $yesterday = Selling::query()
            ->select(
                DB::raw('COUNT(*) as total_transactions'),
            )
            ->isPaid()
            ->whereBetween('created_at', [$startOfYesterday, $startOfDay])
            ->first();

        $todayMembers = Member::whereDate('created_at', now($timezone))->count();

        $txnTrend = '';
        $txnColor = 'success';
        if ($yesterday->total_transactions > 0) {
            $pct = (($today->total_transactions - $yesterday->total_transactions) / $yesterday->total_transactions) * 100;
            $txnTrend = ($pct >= 0 ? '+' : '') . round($pct) . '% ' . __('vs kemarin');
            $txnColor = $pct >= 0 ? 'success' : 'danger';
        }

        return [
            Stat::make(__('Transaksi Hari Ini'), $today->total_transactions)
                ->description($txnTrend)
                ->descriptionIcon($txnColor === 'success' ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($txnColor),
            Stat::make(__('Rata-rata per Transaksi'), 'Rp ' . Number::format($today->avg_transaction, 0, ',', '.'))
                ->descriptionIcon('heroicon-m-calculator')
                ->color('info'),
            Stat::make(__('Total Item Terjual'), number_format($today->total_items))
                ->descriptionIcon('heroicon-m-shopping-bag')
                ->color('primary'),
            Stat::make(__('Member Baru Hari Ini'), $todayMembers)
                ->descriptionIcon('heroicon-m-user-plus')
                ->color('warning'),
        ];
    }
}
