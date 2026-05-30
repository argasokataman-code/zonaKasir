<?php

namespace Tests\Feature\E2E;

use App\Models\Tenants\User;
use App\Models\Tenants\CartItem;
use App\Models\Tenants\Member;
use App\Models\Tenants\Product;
use App\Models\Tenants\Selling;
use Illuminate\Http\Response;
use Tests\RefreshDatabaseWithTenant;

uses(RefreshDatabaseWithTenant::class);

describe('Transaction & POS E2E Flow', function () {
    
    describe('Selling Transaction', function () {
        it('can create selling transaction', function () {
            $user = User::first();
            $product = Product::factory()->create();
            
            $response = $this->actingAs($user, 'sanctum')
                ->postJson('/api/transaction/selling', [
                    'selling_details' => [
                        [
                            'product_id' => $product->id,
                            'quantity' => 2,
                            'price' => $product->selling_price * 2,
                        ],
                    ],
                    'payment_method_id' => 1,
                    'total_price' => $product->selling_price * 2,
                    'sub_total' => $product->selling_price * 2,
                    'tax' => 0,
                    'discount' => 0,
                ]);
            
            expect($response->status())->toBe(Response::HTTP_CREATED);
            expect($response->json())->toHaveKey('data');
            expect($response->json('data'))->toHaveKey('id');
        });

        it('can list selling transactions', function () {
            Selling::factory(10)->create();
            $user = User::first();
            
            $response = $this->actingAs($user, 'sanctum')
                ->getJson('/api/transaction/selling');
            
            expect($response->status())->toBe(Response::HTTP_OK);
            expect($response->json())->toHaveKey('data');
        });

        it('can get single selling transaction', function () {
            $selling = Selling::factory()->create();
            $user = User::first();
            
            $response = $this->actingAs($user, 'sanctum')
                ->getJson("/api/transaction/selling/{$selling->id}");
            
            expect($response->status())->toBe(Response::HTTP_OK);
            expect($response->json('data.id'))->toBe($selling->id);
        });

        it('fails to create selling without required payment method', function () {
            $user = User::first();
            $product = Product::factory()->create();
            
            $response = $this->actingAs($user, 'sanctum')
                ->postJson('/api/transaction/selling', [
                    'selling_details' => [
                        [
                            'product_id' => $product->id,
                            'quantity' => 2,
                        ],
                    ],
                    'total_price' => 20000,
                ]);
            
            expect($response->status())->toBe(Response::HTTP_UNPROCESSABLE_ENTITY);
        });
    });

    describe('Cash Drawer Management', function () {
        it('can open cash drawer', function () {
            $user = User::first();
            
            $response = $this->actingAs($user, 'sanctum')
                ->postJson('/api/transaction/cash-drawer', [
                    'opening_balance' => 100000,
                ]);
            
            expect($response->status())->toBe(Response::HTTP_OK);
        });

        it('can show cash drawer status', function () {
            $user = User::first();
            
            $response = $this->actingAs($user, 'sanctum')
                ->getJson('/api/transaction/cash-drawer');
            
            expect($response->status())->toBe(Response::HTTP_OK);
        });

        it('can close cash drawer', function () {
            $user = User::first();
            
            // Open first
            $this->actingAs($user, 'sanctum')
                ->postJson('/api/transaction/cash-drawer', [
                    'opening_balance' => 100000,
                ]);
            
            // Then close
            $response = $this->actingAs($user, 'sanctum')
                ->postJson('/api/transaction/cash-drawer/close', [
                    'closing_balance' => 150000,
                ]);
            
            expect($response->status())->toBe(Response::HTTP_OK);
        });
    });

    describe('Dashboard Metrics', function () {
        it('can get total revenue', function () {
            Selling::factory(5)->create();
            $user = User::first();
            
            $response = $this->actingAs($user, 'sanctum')
                ->getJson('/api/transaction/dashboard/total-revenue');
            
            expect($response->status())->toBe(Response::HTTP_OK);
            expect($response->json())->toHaveKey('data');
        });

        it('can get total gross profit', function () {
            Selling::factory(5)->create();
            $user = User::first();
            
            $response = $this->actingAs($user, 'sanctum')
                ->getJson('/api/transaction/dashboard/total-gross-profit');
            
            expect($response->status())->toBe(Response::HTTP_OK);
        });

        it('can get total sales', function () {
            Selling::factory(5)->create();
            $user = User::first();
            
            $response = $this->actingAs($user, 'sanctum')
                ->getJson('/api/transaction/dashboard/total-sales');
            
            expect($response->status())->toBe(Response::HTTP_OK);
        });
    });
});
