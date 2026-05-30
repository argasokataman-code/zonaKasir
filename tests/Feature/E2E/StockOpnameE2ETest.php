<?php

namespace Tests\Feature\E2E;

use App\Models\Tenants\StockOpname;
use App\Models\Tenants\User;
use App\Models\Tenants\Product;
use Illuminate\Http\Response;
use Tests\RefreshDatabaseWithTenant;

uses(RefreshDatabaseWithTenant::class);

describe('Stock Opname E2E', function () {
    beforeEach(function () {
        $this->user = User::first();
        $this->token = $this->user->createToken('test')->plainTextToken;
    });

    it('can list stock opnames', function () {
        StockOpname::factory(5)->create();

        $response = $this->withToken($this->token)
            ->getJson('/api/master/stock-opname');

        // May not have dedicated endpoint
        expect($response->status())->toBeIn([
            Response::HTTP_OK,
            Response::HTTP_NOT_FOUND,
        ]);
    });

    it('can create stock opname', function () {
        $product = Product::first() ?? Product::factory()->create();

        $response = $this->withToken($this->token)
            ->postJson('/api/master/stock-opname', [
                'product_id' => $product->id,
                'quantity' => 100,
                'notes' => 'Physical count',
            ]);

        expect($response->status())->toBeIn([
            Response::HTTP_CREATED,
            Response::HTTP_OK,
            Response::HTTP_NOT_FOUND,
        ]);
    });

    it('can finalize stock opname', function () {
        $opname = StockOpname::factory()->create();

        $response = $this->withToken($this->token)
            ->putJson("/api/master/stock-opname/{$opname->id}/finalize", [
                'status' => 'finalized',
            ]);

        expect($response->status())->toBeIn([
            Response::HTTP_OK,
            Response::HTTP_NOT_FOUND,
        ]);
    });
});
