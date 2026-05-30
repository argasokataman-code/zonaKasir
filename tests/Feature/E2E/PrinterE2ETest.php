<?php

namespace Tests\Feature\E2E;

use App\Models\Tenants\Printer;
use App\Models\Tenants\User;
use Illuminate\Http\Response;
use Tests\RefreshDatabaseWithTenant;

uses(RefreshDatabaseWithTenant::class);

describe('Printer Configuration E2E', function () {
    beforeEach(function () {
        $this->user = User::first();
        $this->token = $this->user->createToken('test')->plainTextToken;
    });

    it('can list printers', function () {
        Printer::factory(3)->create();

        $response = $this->withToken($this->token)
            ->getJson('/api/printer');

        expect($response->status())->toBe(Response::HTTP_OK);
        expect($response->json()['data'])->toBeArray();
    });

    it('can create printer configuration', function () {
        $response = $this->withToken($this->token)
            ->postJson('/api/printer', [
                'name' => 'Printer POS 1',
                'path' => '/dev/lp0',
                'type' => 'thermal',
            ]);

        expect($response->status())->toBeIn([Response::HTTP_CREATED, Response::HTTP_OK]);
    });

    it('can update printer settings', function () {
        $printer = Printer::factory()->create();

        $response = $this->withToken($this->token)
            ->putJson("/api/printer/{$printer->id}", [
                'name' => 'Updated Printer Name',
            ]);

        expect($response->status())->toBe(Response::HTTP_OK);
    });

    it('can delete printer', function () {
        $printer = Printer::factory()->create();

        $response = $this->withToken($this->token)
            ->deleteJson("/api/printer/{$printer->id}");

        expect($response->status())->toBeIn([
            Response::HTTP_NO_CONTENT,
            Response::HTTP_OK,
        ]);
    });

    it('enforces permission to manage printers', function () {
        $response = $this->getJson('/api/printer');

        expect($response->status())->toBe(Response::HTTP_UNAUTHORIZED);
    });
});
