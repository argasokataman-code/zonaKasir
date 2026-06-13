<?php

namespace App\Filament\Tenant\Resources\MemberResource\Widgets;

use App\Models\Tenants\Member;
use App\Models\Tenants\Selling;
use App\Models\Tenants\Setting;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Carbon;
use Illuminate\Support\Number;

class MemberStatsWidget extends BaseWidget
{
    protected static ?string $pollingInterval = null;

    protected function getStats(): array
    {
        $totalMembers = Member::count();
        $newThisMonth = Member::whereBetween('created_at', [
            Carbon::now()->startOfMonth(),
            Carbon::now()->endOfMonth(),
        ])->count();

        $topSpender = Selling::query()
            ->selectRaw('member_id, SUM(grand_total_price) as total_spent')
            ->whereNotNull('member_id')
            ->whereBetween('created_at', [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()])
            ->groupBy('member_id')
            ->orderByDesc('total_spent')
            ->with('member')
            ->first();

        $topSpenderName = $topSpender?->member?->name ?? '-';
        $topSpenderAmount = $topSpender ? Number::currency($topSpender->total_spent, Setting::get('currency', 'IDR')) : '-';

        return [
            Stat::make(__('Total members'), Number::format($totalMembers))
                ->description(__('All time'))
                ->color('primary'),
            Stat::make(__('New this month'), Number::format($newThisMonth))
                ->description(Carbon::now()->translatedFormat('F Y'))
                ->color('success'),
            Stat::make(__('Top spender'), $topSpenderAmount)
                ->description($topSpenderName)
                ->color('warning'),
        ];
    }
}
