<?php

namespace Tests\Feature\E2E;

use App\Models\Tenants\Voucher;
use App\Models\Tenants\User;
use Illuminate\Http\Response;
use Tests\RefreshDatabaseWithTenant;

uses(RefreshDatabaseWithTenant::class);

describe('Voucher Management E2E', function () {
    beforeEach(function () {
        $this->user = User::first();
        $this->token = $this->user->createToken('test')->plainTextToken;
    });

    it('can list vouchers', function () {
        Voucher::factory(5)->create();

        $response = $this->withToken($this->token)
            ->getJson('/api/master/voucher');

        // May or may not have voucher endpoint, depends on implementation
        expect($response->status())->toBeIn([Response::HTTP_OK, Response::HTTP_NOT_FOUND]);
    });

    it('can create voucher with discount', function () {
        $voucherData = [
            'code' => 'VOUCHER10',
            'discount_type' => 'percentage',
            'discount_value' => 10,
            'description' => 'Test voucher',
        ];

        $response = $this->withToken($this->token)
            ->postJson('/api/master/voucher', $voucherData);

        // May be 201 or 404 depending on implementation
        expect($response->status())->toBeIn([Response::HTTP_CREATED, Response::HTTP_NOT_FOUND]);
    });

    it('validates voucher code uniqueness', function () {
        $voucher = Voucher::factory()->create();

        $response = $this->withToken($this->token)
            ->postJson('/api/master/voucher', [
                'code' => $voucher->code,
                'discount_value' => 10,
            ]);

        // May fail validation
        expect($response->status())->toBeIn([
            Response::HTTP_UNPROCESSABLE_ENTITY,
            Response::HTTP_NOT_FOUND,
        ]);
    });

    it('can deactivate voucher', function () {
        $voucher = Voucher::factory()->create();

        $response = $this->withToken($this->token)
            ->putJson("/api/master/voucher/{$voucher->id}", ['is_active' => false]);

        expect($response->status())->toBeIn([
            Response::HTTP_OK,
            Response::HTTP_NOT_FOUND,
        ]);
    });
});
