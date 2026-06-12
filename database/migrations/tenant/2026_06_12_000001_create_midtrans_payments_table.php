<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('midtrans_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('selling_id')->constrained()->onDelete('cascade');
            $table->string('order_id')->unique(); // format: T{tenant_id}-{microtime}-{random(4)}
            $table->string('midtrans_transaction_id')->nullable();
            $table->double('gross_amount');
            $table->string('payment_type')->nullable(); // credit_card, gopay, bank_transfer, qris, etc.
            $table->string('payment_channel')->nullable(); // BCA, BNI, Mandiri, etc.
            $table->string('status'); // pending, settlement, capture, expire, deny, cancel, refund
            $table->double('fee_midtrans')->default(0);
            $table->double('fee_platform')->default(0);
            $table->double('net_amount')->default(0);
            $table->timestamp('paid_at')->nullable();
            $table->json('notification_payload')->nullable();
            $table->timestamps();

            $table->index(['status', 'paid_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('midtrans_payments');
    }
};