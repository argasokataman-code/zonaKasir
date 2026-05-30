<?php

namespace Tests\Feature\E2E;

use App\Models\Tenants\Supplier;
use App\Models\Tenants\User;
use Illuminate\Http\Response;
use Tests\RefreshDatabaseWithTenant;

uses(RefreshDatabaseWithTenant::class);

describe('Supplier Management E2E', function () {
    beforeEach(function () {
        $this->user = User::first();
        $this->token = $this->user->createToken('test')->plainTextToken;
    });

    it('can list suppliers with pagination', function () {
        // Create test suppliers
        Supplier::factory(15)->create();

        $response = $this->withToken($this->token)
            ->getJson('/api/master/supplier?per_page=10');

        expect($response->status())->toBe(Response::HTTP_OK);
        expect($response->json()['data'])->toBeArray();
        expect(count($response->json()['data']))->toBeLessThanOrEqual(10);
    });

    it('can create new supplier with required fields', function () {
        $supplierData = [
            'name' => 'PT Supplier Test',
            'phone' => '08123456789',
            'address' => 'Jl. Supplier No. 1',
        ];

        $response = $this->withToken($this->token)
            ->postJson('/api/master/supplier', $supplierData);

        expect($response->status())->toBe(Response::HTTP_CREATED);
        expect($response->json()['data']['name'])->toBe($supplierData['name']);
        expect(Supplier::where('name', $supplierData['name'])->exists())->toBeTrue();
    });

    it('validates required fields when creating supplier', function () {
        $response = $this->withToken($this->token)
            ->postJson('/api/master/supplier', []);

        expect($response->status())->toBe(Response::HTTP_UNPROCESSABLE_ENTITY);
    });

    it('can read single supplier details', function () {
        $supplier = Supplier::factory()->create();

        $response = $this->withToken($this->token)
            ->getJson("/api/master/supplier/{$supplier->id}");

        expect($response->status())->toBe(Response::HTTP_OK);
        expect($response->json()['data']['name'])->toBe($supplier->name);
    });

    it('can update supplier information', function () {
        $supplier = Supplier::factory()->create();
        $updatedData = ['name' => 'Updated Supplier Name'];

        $response = $this->withToken($this->token)
            ->putJson("/api/master/supplier/{$supplier->id}", $updatedData);

        expect($response->status())->toBe(Response::HTTP_OK);
        expect($response->json()['data']['name'])->toBe($updatedData['name']);
        expect($supplier->fresh()->name)->toBe($updatedData['name']);
    });

    it('can delete supplier', function () {
        $supplier = Supplier::factory()->create();

        $response = $this->withToken($this->token)
            ->deleteJson("/api/master/supplier/{$supplier->id}");

        expect($response->status())->toBe(Response::HTTP_NO_CONTENT);
        expect(Supplier::find($supplier->id))->toBeNull();
    });

    it('returns 404 for non-existent supplier', function () {
        $response = $this->withToken($this->token)
            ->getJson('/api/master/supplier/99999');

        expect($response->status())->toBe(Response::HTTP_NOT_FOUND);
    });

    it('requires authentication to manage suppliers', function () {
        $response = $this->getJson('/api/master/supplier');
        expect($response->status())->toBe(Response::HTTP_UNAUTHORIZED);
    });
});
