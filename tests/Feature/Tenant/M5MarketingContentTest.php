<?php

use App\Models\Tenants\About;
use App\Models\Tenants\PaymentMethod;
use App\Models\Tenants\Product;
use App\Services\Tenants\MarketingContentService;
use App\Services\Tenants\SellingService;
use Tests\RefreshDatabaseWithTenant;

uses(RefreshDatabaseWithTenant::class);

describe('M5 — Best Seller & Auto Caption (FEAT-8.2, 8.4)', function () {
    beforeEach(function () {
        \Laravel\Pennant\Feature::activate(\App\Features\ProductStock::class);

        $this->productA = Product::factory()->create([
            'stock' => 100, 'is_non_stock' => false,
            'selling_price' => 10000, 'initial_price' => 5000,
        ]);
        $this->productB = Product::factory()->create([
            'stock' => 100, 'is_non_stock' => false,
            'selling_price' => 20000, 'initial_price' => 10000,
        ]);
        $this->cash = PaymentMethod::where('name', 'Cash')->first();
        $this->service = app(SellingService::class);

        About::first()->update(['shop_name' => 'Zona Coffee']);
    });

    function sell($test, $productId, $qty)
    {
        $data = [
            'products' => [['product_id' => $productId, 'qty' => $qty, 'price' => 20000 * $qty, 'discount_price' => 0]],
            'payed_money' => 20000 * $qty,
            'total_price' => 20000 * $qty,
            'discount_price' => 0,
            'payment_method_id' => $test->cash->id,
            'friend_price' => false,
            'cart_uuid' => \Illuminate\Support\Str::uuid(),
        ];
        $data = array_merge($data, $test->service->mapProductRequest($data));
        $test->service->create($data);
    }

    test('MC-1: best seller urut dari qty terbanyak', function () {
        sell($this, $this->productA->id, 2);
        sell($this, $this->productB->id, 5);

        $best = app(MarketingContentService::class)->bestSellers();

        expect($best)->toHaveCount(2);
        expect($best->first()->product->name)->toBe($this->productB->name);
        expect($best->first()->total_qty)->toBe(5.0);
    });

    test('MC-2: caption menyebut nama kafe + menu terlaris + qty', function () {
        sell($this, $this->productA->id, 3);

        $caption = app(MarketingContentService::class)->caption();

        expect($caption)->toContain('Zona Coffee');
        expect($caption)->toContain($this->productA->name);
        expect($caption)->toContain('3');
    });

    test('MC-3: tanpa penjualan caption beri tahu belum ada', function () {
        $caption = app(MarketingContentService::class)->caption();

        expect($caption)->toContain('Belum ada penjualan');
    });
});
