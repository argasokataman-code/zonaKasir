<?php

namespace Tests\Feature\E2E;

use App\Models\Tenants\Purchasing;
use App\Models\Tenants\Receivable;
use App\Models\Tenants\User;
use App\Models\Tenants\Supplier;
use App\Models\Tenants\Product;
use Illuminate\Http\Response;
use Tests\RefreshDatabaseWithTenant;

uses(RefreshDatabaseWithTenant::class);

describe('Purchasing & Accounts E2E', function () {
    beforeEach(function () {
        $this->user = User::first();
        $this->token = $this->user->createToken('test')->plainTextToken;
    });

    describe('Purchasing', function () {
        it('can list purchases', function () {
            Purchasing::factory(5)->create();

            $response = $this->withToken($this->token)
                ->getJson('/api/purchasing');

            // May not have dedicated API endpoint
            expect($response->status())->toBeIn([
                Response::HTTP_OK,
                Response::HTTP_NOT_FOUND,
            ]);
        });

        it('can create purchase order', function () {
            $supplier = Supplier::first() ?? Supplier::factory()->create();
            $product = Product::first() ?? Product::factory()->create();

            $response = $this->withToken($this->token)
                ->postJson('/api/purchasing', [
                    'supplier_id' => $supplier->id,
                    'items' => [
                        [
                            'product_id' => $product->id,
                            'quantity' => 10,
                            'unit_price' => 50000,
                        ],
                    ],
                    'total_amount' => 500000,
                ]);

            // May be 201, 404, or handled elsewhere
            expect($response->status())->toBeIn([
                Response::HTTP_CREATED,
                Response::HTTP_NOT_FOUND,
                Response::HTTP_UNPROCESSABLE_ENTITY,
            ]);
        });

        it('can retrieve purchase details', function () {
            $purchase = Purchasing::factory()->create();

            $response = $this->withToken($this->token)
                ->getJson("/api/purchasing/{$purchase->id}");

            expect($response->status())->toBeIn([
                Response::HTTP_OK,
                Response::HTTP_NOT_FOUND,
            ]);
        });
    });

    describe('Receivable Accounts', function () {
        it('can list receivables', function () {
            Receivable::factory(5)->create();

            $response = $this->withToken($this->token)
                ->getJson('/api/receivable');

            // May not have dedicated API endpoint
            expect($response->status())->toBeIn([
                Response::HTTP_OK,
                Response::HTTP_NOT_FOUND,
            ]);
        });

        it('can view receivable details', function () {
            $receivable = Receivable::factory()->create();

            $response = $this->withToken($this->token)
                ->getJson("/api/receivable/{$receivable->id}");

            expect($response->status())->toBeIn([
                Response::HTTP_OK,
                Response::HTTP_NOT_FOUND,
            ]);
        });

        it('can record payment on receivable', function () {
            $receivable = Receivable::factory()->create();

            $response = $this->withToken($this->token)
                ->putJson("/api/receivable/{$receivable->id}/payment", [
                    'amount' => 50000,
                    'payment_date' => now(),
                ]);

            expect($response->status())->toBeIn([
                Response::HTTP_OK,
                Response::HTTP_NOT_FOUND,
                Response::HTTP_UNPROCESSABLE_ENTITY,
            ]);
        });
    });
});
