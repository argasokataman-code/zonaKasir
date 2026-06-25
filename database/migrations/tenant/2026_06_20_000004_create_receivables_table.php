<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('receivables', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id')->nullable()->index();
            $table->foreignId('member_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('selling_id')->nullable()->constrained()->nullOnDelete();
            $table->double('total_receivable');
            $table->double('rest_receivable');
            $table->date('due_date');
            $table->date('last_billing_date')->nullable();
            $table->integer('total_billing_via_whatsapp')->default(0);
            $table->boolean('status')->default(false);
            $table->timestamps();
        });

        Schema::create('receivable_items', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id')->nullable()->index();
            $table->foreignId('receivable_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('receivable_payments', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id')->nullable()->index();
            $table->foreignId('receivable_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('payment_method_id')->nullable()->constrained()->nullOnDelete();
            $table->double('amount');
            $table->date('payment_date');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('receivable_payments');
        Schema::dropIfExists('receivable_items');
        Schema::dropIfExists('receivables');
    }
};
