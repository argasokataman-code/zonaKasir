<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settlements', function (Blueprint $table) {
            $table->id();
            $table->date('period_start');
            $table->date('period_end');
            $table->double('total_gross');
            $table->double('total_fee_midtrans');
            $table->double('total_fee_platform');
            $table->double('total_net');
            $table->unsignedBigInteger('transaction_count');
            $table->enum('status', ['pending', 'approved', 'partial', 'disbursed', 'failed'])->default('pending');
            $table->text('notes')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('disbursed_at')->nullable();
            $table->timestamps();

            $table->unique(['period_start', 'period_end']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('settlements');
    }
};
