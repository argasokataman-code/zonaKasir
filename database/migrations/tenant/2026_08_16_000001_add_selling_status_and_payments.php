<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. sellings: add status + cart_uuid (idempotency source)
        Schema::table('sellings', function (Blueprint $table) {
            $table->string('status', 20)->default('paid')
                ->comment('open, partially_paid, paid, cancelled');
            $table->uuid('cart_uuid')->nullable()
                ->comment('idempotency key: satu cart = satu selling');
        });

        // Backfill: existing rows are all final transactions
        DB::statement("UPDATE sellings SET status = CASE WHEN is_paid = true THEN 'paid' ELSE 'cancelled' END");

        // 2. Anti double-bill: max 1 active bill per table
        // Partial unique index (Postgres) — 1 open/partially_paid per table
        DB::statement("
            CREATE UNIQUE INDEX selling_one_open_per_table
            ON sellings (tenant_id, table_id)
            WHERE status IN ('open','partially_paid') AND table_id IS NOT NULL
        ");

        // Anti duplicate code (NC-1): defense in depth setelah observer lock
        DB::statement("
            CREATE UNIQUE INDEX sellings_code_unique_per_tenant
            ON sellings (tenant_id, code)
        ");

        // 3. selling_payments: multi-payment support (cash + QRIS campur)
        Schema::create('selling_payments', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id');
            $table->foreignId('selling_id')->constrained()->onDelete('cascade');
            $table->foreignId('payment_method_id')->nullable()->constrained();
            $table->double('amount');
            $table->boolean('is_cash')->default(true);
            $table->string('status', 20)->default('pending')
                ->comment('pending, success, failed, expired');
            $table->string('idempotency_key');
            $table->string('midtrans_ref', 64)->nullable();
            $table->timestamp('payment_date')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'idempotency_key']);
            $table->unique('midtrans_ref');
            $table->index(['selling_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('selling_payments');
        DB::statement('DROP INDEX IF EXISTS selling_one_open_per_table');
        Schema::table('sellings', function (Blueprint $table) {
            $table->dropColumn(['status', 'cart_uuid']);
        });
    }
};
