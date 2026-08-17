<?php

use App\Models\Tenants\About;
use App\Models\Tenants\Category;
use App\Models\Tenants\PaymentMethod;
use App\Models\Tenants\Product;
use App\Services\Tenants\SellingService;
use Tests\RefreshDatabaseWithTenant;
use function Pest\Laravel\get;

uses(RefreshDatabaseWithTenant::class);

describe('M5 — Menu Digital + Favorit (FEAT-8.5)', function () {
    beforeEach(function () {
        \Laravel\Pennant\Feature::activate(\App\Features\ProductStock::class);

        $this->category = Category::factory()->create(['name' => 'Minuman']);
        $this->productA = Product::factory()->create([
            'stock' => 100, 'is_non_stock' => false,
            'selling_price' => 15000, 'initial_price' => 5000,
            'category_id' => $this->category->id, 'show' => true, 'name' => 'Kopi Susu',
        ]);
        $this->productB = Product::factory()->create([
            'stock' => 100, 'is_non_stock' => false,
            'selling_price' => 25000, 'initial_price' => 10000,
            'category_id' => $this->category->id, 'show' => false, 'name' => 'Matcha',
        ]);
        $this->cash = PaymentMethod::where('name', 'Cash')->first();
        $this->service = app(SellingService::class);
    });

    function sellMenu($test, $productId, $qty)
    {
        $data = [
            'products' => [['product_id' => $productId, 'qty' => $qty, 'price' => 15000 * $qty, 'discount_price' => 0]],
            'payed_money' => 15000 * $qty,
            'total_price' => 15000 * $qty,
            'discount_price' => 0,
            'payment_method_id' => $test->cash->id,
            'friend_price' => false,
            'cart_uuid' => \Illuminate\Support\Str::uuid(),
        ];
        $data = array_merge($data, $test->service->mapProductRequest($data));
        $test->service->create($data);
    }

    test('MENU-1: about punya menu_token + menuUrl', function () {
        $about = About::first();

        expect($about->menu_token)->not->toBeNull();
        expect($about->menuUrl())->toContain('/menu/'.$about->menu_token);
        expect($about->menuUrl(3))->toBe($about->menuUrl().'?table=3');
    });

    test('MENU-2: halaman menu hanya tampilkan produk show=true', function () {
        sellMenu($this, $this->productA->id, 1);
        $about = About::first();

        $response = get($about->menuUrl())->assertStatus(200);

        $response->assertSee('Kopi Susu');
        $response->assertDontSee('Matcha');
        $response->assertSee('Minuman');
    });

    test('MENU-3: produk paling laris dapat badge Favorit', function () {
        sellMenu($this, $this->productA->id, 9);
        $about = About::first();

        get($about->menuUrl())->assertStatus(200)->assertSee('Favorit');
    });

    test('MENU-4: token invalid → 404', function () {
        get('/menu/token-salah')->assertStatus(404);
    });
});
