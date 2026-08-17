<?php

namespace App\Services\Tenants;

use App\Models\Tenants\Product;
use App\Models\Tenants\SellingDetail;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class MenuDigitalService
{
    public function items(?int $favoriteLimit = 5): Collection
    {
        $favorites = SellingDetail::query()
            ->select('product_id', DB::raw('SUM(qty) as total_qty'))
            ->groupBy('product_id')
            ->orderByDesc('total_qty')
            ->limit($favoriteLimit)
            ->pluck('product_id')
            ->all();

        return Product::query()
            ->where('show', true)
            ->with('category:id,name')
            ->orderBy('name')
            ->get()
            ->map(fn ($product) => [
                'id' => $product->id,
                'name' => $product->name,
                'category' => $product->category?->name,
                'price' => $product->selling_price,
                'is_favorite' => in_array($product->id, $favorites),
            ]);
    }
}
