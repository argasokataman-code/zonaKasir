<?php

use App\Models\Tenants\About;
use App\Models\Tenants\Member;
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

describe('M5 — Jam Sepi/Ramai & Kartu Member (FEAT-8.3, 8.6)', function () {
    beforeEach(function () {
        \Laravel\Pennant\Feature::activate(\App\Features\ProductStock::class);

        $this->product = Product::factory()->create([
            'stock' => 100, 'is_non_stock' => false,
            'selling_price' => 10000, 'initial_price' => 5000,
        ]);
        $this->cash = PaymentMethod::where('name', 'Cash')->first();
        $this->service = app(SellingService::class);
        $this->memberA = Member::factory()->create(['name' => 'Budi']);
        $this->memberB = Member::factory()->create(['name' => 'Sari']);
    });

    function sellWithMember($test, $productId, $memberId, $hour)
    {
        $data = [
            'products' => [['product_id' => $productId, 'qty' => 1, 'price' => 10000, 'discount_price' => 0]],
            'payed_money' => 10000,
            'total_price' => 10000,
            'discount_price' => 0,
            'payment_method_id' => $test->cash->id,
            'friend_price' => false,
            'member_id' => $memberId,
            'date' => now()->setTime($hour, 0),
            'cart_uuid' => \Illuminate\Support\Str::uuid(),
        ];
        $data = array_merge($data, $test->service->mapProductRequest($data));
        $test->service->create($data);
    }

    test('PH-1: peakHours mengembalikan 24 jam + tandai jam ramai', function () {
        sellWithMember($this, $this->product->id, $this->memberA->id, 8);
        sellWithMember($this, $this->product->id, $this->memberA->id, 19);
        sellWithMember($this, $this->product->id, $this->memberA->id, 19);

        $hours = app(MarketingContentService::class)->peakHours();

        expect($hours)->toHaveCount(24);
        expect($hours[8]['total'])->toBe(1);
        expect($hours[19]['total'])->toBe(2);
        expect($hours[19]['busy'])->toBeTrue();
        expect($hours[8]['busy'])->toBeFalse();
    });

    test('MT-1: memberThanks urut dari kunjungan terbanyak + pesan apresiasi', function () {
        sellWithMember($this, $this->product->id, $this->memberA->id, 10);
        sellWithMember($this, $this->product->id, $this->memberA->id, 11);
        sellWithMember($this, $this->product->id, $this->memberB->id, 12);

        $thanks = app(MarketingContentService::class)->memberThanks();

        expect($thanks)->toHaveCount(2);
        expect($thanks->first()['name'])->toBe('Budi');
        expect($thanks->first()['visits'])->toBe(2);
        expect($thanks->first()['message'])->toContain('2x');
        expect($thanks->last()['visits'])->toBe(1);
    });

    test('MT-2: tanpa kunjungan member daftar kosong', function () {
        $thanks = app(MarketingContentService::class)->memberThanks();

        expect($thanks)->toBeEmpty();
    });
});
