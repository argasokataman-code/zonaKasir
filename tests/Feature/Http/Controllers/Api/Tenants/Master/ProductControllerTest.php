<?php

use App\Models\Tenants\Category;
use App\Models\Tenants\Product;
use App\Models\Tenants\User;
use Illuminate\Http\Response;
use Tests\RefreshDatabaseWithTenant;

use function Pest\Laravel\actingAs;

uses(RefreshDatabaseWithTenant::class);

test("can'\t create product", function () {
    $user = User::first();
    actingAs($user)->postJson('/api/master/product', [])
        ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
});

test('can control pagination size with per_page query', function () {
    $user = User::first();
    $category = Category::factory()->create();
    Product::factory()->count(20)->create([
        'category_id' => $category->id,
    ]);

    actingAs($user, 'sanctum')
        ->getJson('/api/master/product?per_page=5')
        ->assertOk()
        ->assertJsonPath('data.meta.per_page', 5)
        ->assertJsonCount(5, 'data.data');
});

test('can fall back to default pagination when per_page is invalid', function () {
    $user = User::first();
    $category = Category::factory()->create();
    Product::factory()->count(20)->create([
        'category_id' => $category->id,
    ]);

    actingAs($user, 'sanctum')
        ->getJson('/api/master/product?per_page=invalid')
        ->assertOk()
        ->assertJsonPath('data.meta.per_page', Product::query()->getModel()->getPerPage())
        ->assertJsonCount(Product::query()->getModel()->getPerPage(), 'data.data');
});

test('can clamp per_page to maximum of 100', function () {
    $user = User::first();
    $category = Category::factory()->create();
    Product::factory()->count(20)->create([
        'category_id' => $category->id,
    ]);

    actingAs($user, 'sanctum')
        ->getJson('/api/master/product?per_page=999')
        ->assertOk()
        ->assertJsonPath('data.meta.per_page', 100)
        ->assertJsonCount(20, 'data.data');
});

test('store persists barcode to barcode relation', function () {
    $user = User::first();
    $category = Category::factory()->create();

    actingAs($user, 'sanctum')
        ->postJson('/api/master/product', [
            'name' => 'Barcode API Product',
            'sku' => 'SKU-BARCODE-001',
            'barcode' => 'API-BAR-001',
            'category' => $category->id,
            'stock' => 10,
            'initial_price' => 10000,
            'selling_price' => 15000,
            'type' => 'product',
            'is_non_stock' => false,
            'expired' => now()->addDay()->toDateString(),
        ])
        ->assertOk();

    $product = Product::query()->where('name', 'Barcode API Product')->firstOrFail();
    expect($product->barcodes()->primary()->active()->value('code'))->toBe('API-BAR-001');
});

test('update persists changed barcode to barcode relation', function () {
    $user = User::first();
    $category = Category::factory()->create();
    $product = Product::factory()->create([
        'category_id' => $category->id,
    ]);

    actingAs($user, 'sanctum')
        ->putJson("/api/master/product/{$product->id}", [
            'barcode' => 'API-BAR-UPDATED',
        ])
        ->assertOk();

    $product->refresh();
    expect($product->barcodes()->primary()->active()->value('code'))->toBe('API-BAR-UPDATED');
});
