<?php

use App\Models\Tenants\Category;
use App\Models\Tenants\Member;
use App\Models\Tenants\Product;
use App\Models\Tenants\User;
use Tests\RefreshDatabaseWithTenant;

use function Pest\Laravel\actingAs;

uses(RefreshDatabaseWithTenant::class);

beforeEach(function () {
    $this->user = User::first();
    $category = Category::factory()->create();
    $this->product = Product::factory()->create([
        'name' => 'Test Product',
        'initial_price' => 10000,
        'selling_price' => 25000,
        'stock' => 10,
        'category_id' => $category->id,
    ]);
    $this->member = Member::factory()->create();
});

test('complete sale workflow creates selling and reduces stock', function () {
    $response = actingAs($this->user)->postJson('/api/transaction/selling', [
        'payed_money' => 100000,
        'friend_price' => false,
        'member_id' => $this->member->getKey(),
        'payment_method_id' => 1,
        'products' => [
            [
                'product_id' => $this->product->id,
                'qty' => 3,
            ],
        ],
    ]);

    $response->assertStatus(201);

    $this->assertDatabaseHas('sellings', [
        'total_qty' => 3,
    ]);
});

test('complete sale workflow with member reference', function () {
    $response = actingAs($this->user)->postJson('/api/transaction/selling', [
        'payed_money' => 50000,
        'friend_price' => false,
        'member_id' => $this->member->getKey(),
        'payment_method_id' => 1,
        'products' => [
            [
                'product_id' => $this->product->id,
                'qty' => 1,
            ],
        ],
    ]);

    $response->assertStatus(201);
});

test('complete sale workflow with insufficient stock returns error', function () {
    $response = actingAs($this->user)->postJson('/api/transaction/selling', [
        'payed_money' => 500000,
        'friend_price' => false,
        'payment_method_id' => 1,
        'products' => [
            [
                'product_id' => $this->product->id,
                'qty' => 100,
            ],
        ],
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['products.0.qty']);
});
