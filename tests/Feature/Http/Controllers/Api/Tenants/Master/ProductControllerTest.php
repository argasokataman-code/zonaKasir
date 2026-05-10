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

test('per_page query controls pagination size', function () {
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

test('invalid per_page falls back to default model pagination size', function () {
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

test('per_page is clamped to a safe maximum', function () {
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
