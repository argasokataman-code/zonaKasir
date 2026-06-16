<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $allowedTypes = ['cash', 'qris', 'credit'];

        // Deactivate all methods not in allowed types
        DB::table('payment_methods')
            ->whereNotIn('payment_type', $allowedTypes)
            ->whereNull('deleted_at')
            ->update(['is_active' => false]);

        // Ensure Cash, QRIS, Piutang exist and are active (using updateOrCreate by payment_type)
        $methods = [
            ['name' => 'Cash', 'payment_type' => 'cash', 'is_cash' => true, 'is_credit' => false, 'is_wallet' => false],
            ['name' => 'QRIS', 'payment_type' => 'qris', 'is_cash' => false, 'is_credit' => false, 'is_wallet' => true],
            ['name' => 'Piutang', 'payment_type' => 'credit', 'is_cash' => false, 'is_credit' => true, 'is_wallet' => false],
        ];

        foreach ($methods as $method) {
            $existing = DB::table('payment_methods')
                ->where('payment_type', $method['payment_type'])
                ->whereNull('deleted_at')
                ->first();

            if ($existing) {
                DB::table('payment_methods')
                    ->where('id', $existing->id)
                    ->update([
                        'is_active' => true,
                        'name' => $method['name'],
                        'is_cash' => $method['is_cash'],
                        'is_debit' => false,
                        'is_credit' => $method['is_credit'],
                        'is_wallet' => $method['is_wallet'],
                    ]);
            } else {
                DB::table('payment_methods')->insert(array_merge($method, [
                    'is_debit' => false,
                    'is_active' => true,
                    'icon' => '',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]));
            }
        }
    }

    public function down(): void
    {
        DB::table('payment_methods')
            ->update(['is_active' => true]);
    }
};
