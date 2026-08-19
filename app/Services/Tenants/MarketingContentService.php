<?php

namespace App\Services\Tenants;

use App\Models\Tenants\About;
use App\Models\Tenants\Member;
use App\Models\Tenants\Selling;
use App\Models\Tenants\SellingDetail;
use Illuminate\Support\Facades\DB;

class MarketingContentService
{
    public function bestSellers(?int $limit = 5): \Illuminate\Support\Collection
    {
        return SellingDetail::query()
            ->select('product_id', DB::raw('SUM(qty) as total_qty'))
            ->whereHas('selling', fn ($q) => $q->whereBetween('date', [today()->startOfDay(), today()->endOfDay()]))
            ->groupBy('product_id')
            ->orderByDesc('total_qty')
            ->limit($limit)
            ->with('product:id,name')
            ->get();
    }

    public function caption(): string
    {
        $shop = About::select('shop_name')->first()?->shop_name ?? 'Kafe kami';
        $top = $this->bestSellers(3);
        $items = $top
            ->map(fn ($row) => sprintf('%s (%s porsi)', $row->product?->name ?? 'Menu', $row->total_qty))
            ->implode(', ');

        $headline = $top->isNotEmpty()
            ? sprintf('Menu terlaris hari ini di %s: %s.', $shop, $items)
            : sprintf('Belum ada penjualan hari ini di %s.', $shop);

        return $headline.' Yuk mampir, biar gak ketinggalan rasa favoritnya!';
    }

    /**
     * FR-8.3 — Distribusi transaksi per jam (7 hari terakhir).
     * `busy = true` untuk jam di atas rata-rata (ramai), selainnya sepi.
     */
    public function peakHours(int $days = 7): \Illuminate\Support\Collection
    {
        $start = now()->subDays($days - 1)->startOfDay();

        $rows = Selling::query()
            ->select(DB::raw('EXTRACT(HOUR FROM date) as hour'), DB::raw('COUNT(*) as total'))
            ->isPaid()
            ->whereBetween('date', [$start, now()->endOfDay()])
            ->groupBy(DB::raw('EXTRACT(HOUR FROM date)'))
            ->get()
            ->keyBy('hour');

        $avg = $rows->avg('total') ?: 0;

        return collect(range(0, 23))->map(fn ($hour) => [
            'hour' => $hour,
            'label' => sprintf('%02d:00', $hour),
            'total' => (int) ($rows->get($hour)?->total ?? 0),
            'busy' => (int) ($rows->get($hour)?->total ?? 0) >= $avg && $avg > 0,
        ]);
    }

    /**
     * FR-8.6 — Kartu "terima kasih" member berdasarkan frekuensi kunjungan bulan ini.
     */
    public function memberThanks(?int $limit = 5): \Illuminate\Support\Collection
    {
        $rows = Selling::query()
            ->select('member_id', DB::raw('COUNT(*) as total'))
            ->whereNotNull('member_id')
            ->isPaid()
            ->whereBetween('date', [now()->startOfMonth(), now()->endOfMonth()])
            ->groupBy('member_id')
            ->orderByDesc('total')
            ->limit($limit)
            ->get();

        $members = Member::query()
            ->whereIn('id', $rows->pluck('member_id'))
            ->get()
            ->keyBy('id');

        return $rows->map(fn ($row) => [
            'name' => $members->get($row->member_id)?->name ?? 'Member',
            'visits' => (int) $row->total,
            'message' => sprintf('Sudah %dx ke sini bulan ini, balik lagi ya!', (int) $row->total),
        ]);
    }
}
