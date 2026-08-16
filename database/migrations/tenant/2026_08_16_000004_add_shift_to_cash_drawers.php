<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // FR-6: shift (open cash drawer → close shift). Kolom baru utk expected
        // vs actual + selisih kas. `cash` lama = opening_balance (backward compat).
        Schema::table('cash_drawers', function (Blueprint $table) {
            $table->string('shift_no', 30)->nullable()
                ->comment('nomor shift, misal SHFT-0001');
            $table->double('opening_amount')->nullable()
                ->comment('uang awal laci');
            $table->double('closing_amount')->nullable()
                ->comment('uang fisik saat tutup shift');
            $table->double('expected_total')->nullable()
                ->comment('Σ penjualan cash selama shift');
            $table->double('actual_total')->nullable()
                ->comment('closing - opening');
            $table->double('difference')->nullable()
                ->comment('actual - expected (selisih kas)');
            $table->timestamp('closed_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('cash_drawers', function (Blueprint $table) {
            $table->dropColumn([
                'shift_no', 'opening_amount', 'closing_amount',
                'expected_total', 'actual_total', 'difference', 'closed_at',
            ]);
        });
    }
};
