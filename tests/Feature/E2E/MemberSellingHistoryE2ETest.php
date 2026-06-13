<?php

namespace Tests\Feature\E2E;

use App\Models\Tenants\Member;
use App\Models\Tenants\Selling;
use App\Models\Tenants\SellingDetail;
use App\Models\Tenants\User;
use Illuminate\Http\Response;
use Tests\RefreshDatabaseWithTenant;

uses(RefreshDatabaseWithTenant::class);

describe('Member Purchase History', function () {
    beforeEach(function () {
        $this->user = User::first();
        $this->token = $this->user->createToken('test')->plainTextToken;
    });

    it('member sellings relationship returns only their transactions', function () {
        $memberA = Member::factory()->create();
        $memberB = Member::factory()->create();

        Selling::factory()
            ->count(2)
            ->for($memberA)
            ->create();

        Selling::factory()
            ->count(3)
            ->for($memberB)
            ->create();

        expect($memberA->sellings()->count())->toBe(2);
        expect($memberB->sellings()->count())->toBe(3);
    });

    it('member sellings are empty when no purchases', function () {
        $member = Member::factory()->create();

        expect($member->sellings()->count())->toBe(0);
    });

    it('view member page renders without error', function () {
        $member = Member::factory()->create();

        $response = $this->actingAs($this->user)
            ->get("/member/members/{$member->id}");

        expect($response->status())->toBe(Response::HTTP_OK);
    });

    it('sellings list page shows member filter', function () {
        $response = $this->withToken($this->token)
            ->getJson('/api/transaction/selling?filter[member_id]=1');

        expect($response->status())->toBe(Response::HTTP_OK);
    });
});
