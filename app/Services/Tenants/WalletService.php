<?php

namespace App\Services\Tenants;

use App\Models\Tenants\Member;
use App\Models\Tenants\Selling;
use App\Models\Tenants\WalletTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class WalletService
{
    public function topUp(Member $member, int $amount, ?string $note = null, ?int $userId = null): WalletTransaction
    {
        if ($amount <= 0) {
            throw new \InvalidArgumentException('Top-up amount must be positive');
        }

        return DB::transaction(function () use ($member, $amount, $note, $userId) {
            $member->increment('wallet_balance', $amount);

            $transaction = WalletTransaction::create([
                'member_id' => $member->id,
                'type' => 'top_up',
                'amount' => $amount,
                'balance_after' => $member->fresh()->wallet_balance,
                'user_id' => $userId,
                'note' => $note ?? 'Manual top-up',
            ]);

            Log::info("Wallet: Member #{$member->id} topped up {$amount} IDR", [
                'member_id' => $member->id,
                'amount' => $amount,
                'user_id' => $userId,
            ]);

            return $transaction;
        });
    }

    public function pay(Member $member, int $amount, Selling $selling): WalletTransaction
    {
        if ($member->wallet_balance < $amount) {
            throw new \InvalidArgumentException('Insufficient wallet balance');
        }

        return DB::transaction(function () use ($member, $amount, $selling) {
            $member->decrement('wallet_balance', $amount);

            $transaction = WalletTransaction::create([
                'member_id' => $member->id,
                'sourceable_type' => Selling::class,
                'sourceable_id' => $selling->id,
                'type' => 'payment',
                'amount' => $amount,
                'balance_after' => $member->fresh()->wallet_balance,
                'note' => "Payment for transaction {$selling->code}",
            ]);

            Log::info("Wallet: Member #{$member->id} paid {$amount} IDR", [
                'member_id' => $member->id,
                'selling_id' => $selling->id,
                'amount' => $amount,
            ]);

            return $transaction;
        });
    }

    public function getBalance(Member $member): int
    {
        return (int) $member->wallet_balance;
    }
}
