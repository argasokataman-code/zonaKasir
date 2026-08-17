<?php

use App\Models\Tenants\About;
use App\Models\Tenants\PaymentMethod;
use App\Models\Tenants\Product;
use App\Models\Tenants\Selling;
use App\Services\Tenants\SellingService;
use Tests\RefreshDatabaseWithTenant;
use function Pest\Laravel\get;

uses(RefreshDatabaseWithTenant::class);

describe('M5 — Struk Digital Shareable (FEAT-8.1)', function () {
    beforeEach(function () {
        \Laravel\Pennant\Feature::activate(\App\Features\ProductStock::class);

        $this->product = Product::factory()->create([
            'stock' => 50, 'is_non_stock' => false,
            'selling_price' => 10000, 'initial_price' => 5000,
        ]);
        $this->cash = PaymentMethod::where('name', 'Cash')->first();
        $this->service = app(SellingService::class);

        About::first()->update(['shop_name' => 'Zona Coffee', 'shop_location' => 'Jalan Raya Kuta No 1']);
    });

    function paidBill($test): Selling
    {
        $data = [
            'products' => [
                ['product_id' => $test->product->id, 'qty' => 2, 'price' => 20000, 'discount_price' => 0],
            ],
            'payed_money' => 20000,
            'total_price' => 20000,
            'discount_price' => 0,
            'payment_method_id' => $test->cash->id,
            'friend_price' => false,
            'cart_uuid' => \Illuminate\Support\Str::uuid(),
        ];
        $data = array_merge($data, $test->service->mapProductRequest($data));

        return $test->service->create($data);
    }

    test('SHARE-1: selling otomatis dapat share_token + URL', function () {
        $selling = paidBill($this);

        expect($selling->share_token)->not->toBeNull();
        expect($selling->share_token)->toHaveLength(32);
        expect($selling->shareUrl())->toContain("/s/{$selling->share_token}");
    });

    test('SHARE-2: halaman publik render item + total + shop_name', function () {
        $selling = paidBill($this);

        $response = get($selling->shareUrl())->assertStatus(200);

        $response->assertSee('Zona Coffee');
        $response->assertSee($this->product->name);
        $response->assertSee('Lunas');
    });

    test('SHARE-3: token tidak valid → 404', function () {
        get('/s/token-tidak-ada')->assertStatus(404);
    });

    test('SHARE-4: selling belum paid (open) tidak bisa di-share', function () {
        $selling = paidBill($this);
        $selling->update(['status' => 'open']);

        get($selling->shareUrl())->assertStatus(404);
    });
});
