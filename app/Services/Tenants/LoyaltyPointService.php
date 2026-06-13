<?php

namespace App\Services\Tenants;

use App\Models\Tenants\LoyaltyPointLog;
use App\Models\Tenants\Member;
use App\Models\Tenants\Selling;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class LoyaltyPointService
{
    private int $earnRate = 10; // 1 point per 10 IDR spent

    private int $redeemRate = 1; // 1 point = 1 IDR discount

    public function earnPoints(Selling $selling): void
    {
        if (! $selling->member_id) {
            return;
        }
        if (! $selling->is_paid) {
            return;
        }

        $amount = (int) floor($selling->grand_total_price);
        if ($amount <= 0) {
            return;
        }

        $points = (int) floor($amount / $this->earnRate);
        if ($points <= 0) {
            return;
        }

        DB::transaction(function () use ($selling, $points) {
            $member = Member::lockForUpdate()->find($selling->member_id);
            if (! $member) {
                return;
            }

            $member->increment('total_points', $points);

            LoyaltyPointLog::create([
                'member_id' => $selling->member_id,
                'sourceable_type' => Selling::class,
                'sourceable_id' => $selling->id,
                'type' => 'earn',
                'points' => $points,
                'balance_after' => $member->fresh()->total_points,
                'note' => "Earned from transaction {$selling->code}",
            ]);

            Log::info("Loyalty: Member #{$selling->member_id} earned {$points} points", [
                'member_id' => $selling->member_id,
                'selling_id' => $selling->id,
                'points' => $points,
            ]);
        });
    }

    public function redeemPoints(Member $member, int $points): int
    {
        if ($member->total_points < $points) {
            throw new \InvalidArgumentException('Insufficient points');
        }

        $amount = $this->getAmountForPoints($points);

        DB::transaction(function () use ($member, $points, $amount) {
            $member->decrement('total_points', $points);

            LoyaltyPointLog::create([
                'member_id' => $member->id,
                'type' => 'redeem',
                'points' => $points,
                'balance_after' => $member->fresh()->total_points,
                'note' => "Redeemed {$points} points for {$amount} IDR discount",
            ]);

            Log::info("Loyalty: Member #{$member->id} redeemed {$points} points", [
                'member_id' => $member->id,
                'points' => $points,
                'amount' => $amount,
            ]);
        });

        return $amount;
    }

    public function getPointsForAmount(float $amount): int
    {
        return (int) floor($amount / $this->earnRate);
    }

    public function getAmountForPoints(int $points): int
    {
        return $points * $this->redeemRate;
    }
}
