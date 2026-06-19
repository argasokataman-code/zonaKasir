<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ledger_entries', function (Blueprint $table) {
            $table->id();
            $table->morphs('ledgerable');
            $table->string('entry_type', 20);
            $table->double('amount');
            $table->double('balance_before');
            $table->double('balance_after');
            $table->string('description');
            $table->string('reference_type');
            $table->unsignedBigInteger('reference_id');
            $table->string('fee_rate_type')->nullable();
            $table->double('fee_rate_value')->nullable();
            $table->timestamps();

            // morphs() already creates index on (ledgerable_type, ledgerable_id)
            $table->index(['reference_type', 'reference_id']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ledger_entries');
    }
};
