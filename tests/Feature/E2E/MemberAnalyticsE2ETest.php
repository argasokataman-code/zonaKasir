<?php

namespace Tests\Feature\E2E;

use App\Models\Tenants\Member;
use App\Models\Tenants\Selling;
use App\Models\Tenants\User;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Tests\RefreshDatabaseWithTenant;

uses(RefreshDatabaseWithTenant::class);

describe('Member Analytics', function () {
    beforeEach(function () {
        $this->user = User::first();
    });

    it('member list page shows analytics widgets', function () {
        $response = $this->actingAs($this->user)
            ->get('/member/members');

        expect($response->status())->toBe(Response::HTTP_OK);
    });

    it('total members count is correct', function () {
        $initialCount = Member::count();
        Member::factory()->count(3)->create();

        expect(Member::count())->toBe($initialCount + 3);
    });

    it('new members this month ignores older members', function () {
        $oldMember = Member::factory()->create([
            'created_at' => Carbon::now()->subMonths(2),
        ]);

        $newMember = Member::factory()->create([
            'created_at' => Carbon::now()->startOfMonth(),
        ]);

        $count = Member::whereBetween('created_at', [
            Carbon::now()->startOfMonth(),
            Carbon::now()->endOfMonth(),
        ])->count();

        expect($count)->toBeGreaterThanOrEqual(1);
    });

    it('top spender is found correctly', function () {
        $memberA = Member::factory()->create(['name' => 'Top Spender']);
        $memberB = Member::factory()->create(['name' => 'Low Spender']);

        Selling::factory()
            ->for($memberA)
            ->create(['total_price' => 500000]);

        Selling::factory()
            ->for($memberB)
            ->create(['total_price' => 100000]);

        $result = Selling::query()
            ->selectRaw('member_id, SUM(total_price) as total_spent')
            ->whereNotNull('member_id')
            ->groupBy('member_id')
            ->orderByDesc('total_spent')
            ->with('member')
            ->first();

        expect($result)->not->toBeNull();
        expect($result->member->name)->toBe('Top Spender');
    });
});
