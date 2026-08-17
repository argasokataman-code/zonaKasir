<?php

namespace App\Services\Tenants;

use App\Models\Tenants\About;
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
}
