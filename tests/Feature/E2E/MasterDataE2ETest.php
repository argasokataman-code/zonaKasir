<?php

namespace Tests\Feature\E2E;

use App\Models\Tenants\User;
use App\Models\Tenants\Category;
use App\Models\Tenants\Product;
use Illuminate\Http\Response;
use Tests\RefreshDatabaseWithTenant;

uses(RefreshDatabaseWithTenant::class);

describe('Master Data Management E2E', function () {
    
    describe('Category Management', function () {
        it('can list categories with pagination', function () {
            Category::factory(15)->create();
            $user = User::first();
            
            $response = $this->actingAs($user, 'sanctum')
                ->getJson('/api/master/category');
            
            expect($response->status())->toBe(Response::HTTP_OK);
            expect($response->json())->toHaveKey('data');
            expect($response->json())->toHaveKey('pagination');
        });

        it('can create new category with authorization', function () {
            $user = User::first();
            
            $response = $this->actingAs($user, 'sanctum')
                ->postJson('/api/master/category', [
                    'name' => 'Electronics',
                    'description' => 'Electronic devices',
                ]);
            
            expect($response->status())->toBe(Response::HTTP_CREATED);
            expect($response->json())->toHaveKey('data');
            expect($response->json('data.name'))->toBe('Electronics');
        });

        it('can read single category', function () {
            $category = Category::factory()->create();
            $user = User::first();
            
            $response = $this->actingAs($user, 'sanctum')
                ->getJson("/api/master/category/{$category->id}");
            
            expect($response->status())->toBe(Response::HTTP_OK);
            expect($response->json('data.name'))->toBe($category->name);
        });

        it('can update category', function () {
            $category = Category::factory()->create();
            $user = User::first();
            
            $response = $this->actingAs($user, 'sanctum')
                ->putJson("/api/master/category/{$category->id}", [
                    'name' => 'Updated Category',
                    'description' => 'Updated description',
                ]);
            
            expect($response->status())->toBe(Response::HTTP_OK);
            expect($response->json('data.name'))->toBe('Updated Category');
        });

        it('can delete category', function () {
            $category = Category::factory()->create();
            $user = User::first();
            
            $response = $this->actingAs($user, 'sanctum')
                ->deleteJson("/api/master/category/{$category->id}");
            
            expect($response->status())->toBe(Response::HTTP_OK);
            $this->assertDatabaseMissing('categories', ['id' => $category->id]);
        });

        it('returns 404 for non-existent category', function () {
            $user = User::first();
            
            $response = $this->actingAs($user, 'sanctum')
                ->getJson('/api/master/category/99999');
            
            expect($response->status())->toBe(Response::HTTP_NOT_FOUND);
        });
    });

    describe('Product Management', function () {
        it('can list products with pagination', function () {
            Product::factory(15)->create();
            $user = User::first();
            
            $response = $this->actingAs($user, 'sanctum')
                ->getJson('/api/master/product');
            
            expect($response->status())->toBe(Response::HTTP_OK);
            expect($response->json())->toHaveKey('data');
            expect($response->json())->toHaveKey('pagination');
        });

        it('can create product with required fields', function () {
            $category = Category::factory()->create();
            $user = User::first();
            
            $response = $this->actingAs($user, 'sanctum')
                ->postJson('/api/master/product', [
                    'name' => 'Test Product',
                    'category_id' => $category->id,
                    'buying_price' => 10000,
                    'selling_price' => 15000,
                    'stock' => 100,
                ]);
            
            expect($response->status())->toBe(Response::HTTP_CREATED);
            expect($response->json('data.name'))->toBe('Test Product');
        });

        it('fails to create product without required fields', function () {
            $user = User::first();
            
            $response = $this->actingAs($user, 'sanctum')
                ->postJson('/api/master/product', [
                    'name' => 'Invalid Product',
                ]);
            
            expect($response->status())->toBe(Response::HTTP_UNPROCESSABLE_ENTITY);
        });

        it('can get product stock', function () {
            $product = Product::factory()->create();
            $user = User::first();
            
            $response = $this->actingAs($user, 'sanctum')
                ->getJson("/api/master/product/{$product->id}/stock");
            
            expect($response->status())->toBe(Response::HTTP_OK);
        });
    });
});
