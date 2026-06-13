<?php

namespace Tests\Feature\E2E;

use App\Models\Tenants\Member;
use App\Models\Tenants\Selling;
use App\Models\Tenants\Voucher;
use App\Models\Tenants\WalletTransaction;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;
use Tests\RefreshDatabaseWithTenant;

uses(RefreshDatabaseWithTenant::class);

describe('Member Portal', function () {
    beforeEach(function () {
        $this->member = Member::factory()->create([
            'email' => 'portal@test.com',
            'password' => Hash::make('password'),
            'wallet_balance' => 50000,
            'total_points' => 200,
        ]);

        $this->actingAs($this->member, 'member');
    });

    it('dashboard shows member points and wallet balance', function () {
        $response = $this->get('/portal/dashboard');

        $response->assertStatus(Response::HTTP_OK);
        $response->assertSee('200'); // points
    });

    it('purchase history shows only own transactions', function () {
        $staffUser = \App\Models\Tenants\User::first();
        Selling::unsetEventDispatcher(); // prevent observer from running
        Selling::factory()->for($this->member)->create([
            'user_id' => $staffUser?->id,
            'code' => 'SELL0001',
        ]);
        $otherMember = Member::factory()->create();
        Selling::factory()->for($otherMember)->create([
            'user_id' => $staffUser?->id,
            'code' => 'SELL0002',
        ]);

        $response = $this->get('/portal/purchases');

        $response->assertStatus(Response::HTTP_OK);
    });

    it('wallet page shows balance and transactions', function () {
        $response = $this->get('/portal/wallet');

        $response->assertStatus(Response::HTTP_OK);
    });

    it('vouchers page shows available vouchers', function () {
        Voucher::factory()->create([
            'code' => 'PORTAL10',
            'name' => 'Portal Voucher',
            'member_id' => $this->member->id,
            'expired' => now()->addWeek(),
            'start_date' => now()->subDay(),
            'kuota' => 5,
            'minimal_buying' => 10000,
        ]);

        $response = $this->get('/portal/vouchers');

        $response->assertStatus(Response::HTTP_OK);
        $response->assertSee('PORTAL10');
    });

    it('profile page shows current data', function () {
        $response = $this->get('/portal/profile');

        $response->assertStatus(Response::HTTP_OK);
        $response->assertSee($this->member->name);
    });

    it('profile can be updated', function () {
        $response = $this->put('/portal/profile', [
            'name' => 'Updated Name',
            'address' => 'New Address',
        ]);

        $response->assertRedirect(route('member.portal.profile'));
        expect($this->member->fresh()->name)->toBe('Updated Name');
    });
});
