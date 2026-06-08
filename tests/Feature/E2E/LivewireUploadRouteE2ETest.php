<?php

namespace Tests\Feature\E2E;

use App\Models\Tenants\User;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Http\Response;
use Tests\RefreshDatabaseWithTenant;

uses(RefreshDatabaseWithTenant::class);

describe('Tenant Livewire Upload Route E2E', function () {
    it('exposes the livewire upload-file route under tenant context', function () {
        $user = User::first();

        $response = $this->withoutMiddleware(VerifyCsrfToken::class)
            ->actingAs($user)
            ->post('/livewire/upload-file', [
                '_token' => csrf_token(),
                'name' => 'file',
                'type' => 'image/jpeg',
            ]);

        expect($response->status())->not->toBe(Response::HTTP_NOT_FOUND);
        expect($response->status())->not->toBe(Response::HTTP_INTERNAL_SERVER_ERROR);
    });

    it('returns a valid tenant response for livewire upload route access', function () {
        $user = User::first();

        $response = $this->withoutMiddleware(VerifyCsrfToken::class)
            ->actingAs($user)
            ->post('/livewire/upload-file', [
                '_token' => csrf_token(),
            ]);

        expect($response->status())->not->toBe(Response::HTTP_NOT_FOUND);
        expect($response->status())->not->toBe(Response::HTTP_INTERNAL_SERVER_ERROR);
    });
});
