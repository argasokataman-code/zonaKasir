<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('withdrawals', function (Blueprint $table) {
            $table->id();
            $table->double('amount');
            $table->string('bank_name');
            $table->string('bank_account_name');
            $table->string('bank_account_number');
            $table->string('bank_code', 10);
            $table->enum('status', ['pending', 'approved', 'rejected', 'processing', 'completed', 'failed'])->default('pending');
            $table->string('idempotency_key')->unique()->index()->nullable();
            $table->string('disburse_id')->nullable();
            $table->json('disburse_response')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('requested_by')->constrained('users');
            $table->foreignId('approved_by')->nullable()->constrained('users');
            $table->foreignId('rejected_by')->nullable()->constrained('users');
            $table->string('rejection_reason')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('withdrawals');
    }
};
