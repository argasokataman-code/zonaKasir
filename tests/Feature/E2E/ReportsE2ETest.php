<?php

namespace Tests\Feature\E2E;

use App\Models\Tenants\User;
use App\Models\Tenants\Selling;
use Illuminate\Http\Response;
use Tests\RefreshDatabaseWithTenant;

uses(RefreshDatabaseWithTenant::class);

describe('Reports E2E', function () {
    
    describe('Selling Report', function () {
        it('can generate selling report', function () {
            Selling::factory(5)->create();
            $user = User::first();
            
            $response = $this->actingAs($user, 'sanctum')
                ->postJson('/api/report/selling', [
                    'start_date' => now()->startOfMonth()->format('Y-m-d'),
                    'end_date' => now()->format('Y-m-d'),
                ]);
            
            expect($response->status())->toBe(Response::HTTP_OK);
            expect($response->json())->toHaveKey('data');
        });

        it('denies report generation without permission', function () {
            $user = User::first();
            $user->revokePermissionTo('generate selling report');
            
            $response = $this->actingAs($user, 'sanctum')
                ->postJson('/api/report/selling', [
                    'start_date' => now()->startOfMonth()->format('Y-m-d'),
                    'end_date' => now()->format('Y-m-d'),
                ]);
            
            expect($response->status())->toBe(Response::HTTP_FORBIDDEN);
        });
    });

    describe('Product Report', function () {
        it('can generate product report', function () {
            $user = User::first();
            
            $response = $this->actingAs($user, 'sanctum')
                ->postJson('/api/report/product', [
                    'start_date' => now()->startOfMonth()->format('Y-m-d'),
                    'end_date' => now()->format('Y-m-d'),
                ]);
            
            expect($response->status())->toBe(Response::HTTP_OK);
        });

        it('denies product report without permission', function () {
            $user = User::first();
            $user->revokePermissionTo('generate product report');
            
            $response = $this->actingAs($user, 'sanctum')
                ->postJson('/api/report/product', [
                    'start_date' => now()->startOfMonth()->format('Y-m-d'),
                    'end_date' => now()->format('Y-m-d'),
                ]);
            
            expect($response->status())->toBe(Response::HTTP_FORBIDDEN);
        });
    });

    describe('Cashier Report', function () {
        it('can generate cashier report', function () {
            $user = User::first();
            
            $response = $this->actingAs($user, 'sanctum')
                ->postJson('/api/report/cashier', [
                    'start_date' => now()->startOfMonth()->format('Y-m-d'),
                    'end_date' => now()->format('Y-m-d'),
                ]);
            
            expect($response->status())->toBe(Response::HTTP_OK);
        });

        it('denies cashier report without permission', function () {
            $user = User::first();
            $user->revokePermissionTo('generate cashier report');
            
            $response = $this->actingAs($user, 'sanctum')
                ->postJson('/api/report/cashier', [
                    'start_date' => now()->startOfMonth()->format('Y-m-d'),
                    'end_date' => now()->format('Y-m-d'),
                ]);
            
            expect($response->status())->toBe(Response::HTTP_FORBIDDEN);
        });
    });

    describe('Report Validation', function () {
        it('validates date format in reports', function () {
            $user = User::first();
            
            $response = $this->actingAs($user, 'sanctum')
                ->postJson('/api/report/selling', [
                    'start_date' => 'invalid-date',
                    'end_date' => 'invalid-date',
                ]);
            
            expect($response->status())->toBe(Response::HTTP_UNPROCESSABLE_ENTITY);
        });

        it('rejects end_date before start_date', function () {
            $user = User::first();
            
            $response = $this->actingAs($user, 'sanctum')
                ->postJson('/api/report/selling', [
                    'start_date' => now()->format('Y-m-d'),
                    'end_date' => now()->subDay()->format('Y-m-d'),
                ]);
            
            expect($response->status())->toBe(Response::HTTP_UNPROCESSABLE_ENTITY);
        });
    });
});
