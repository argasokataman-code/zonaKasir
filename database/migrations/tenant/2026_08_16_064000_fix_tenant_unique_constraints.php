<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Tabel multi-tenant: unique constraint harus include tenant_id.
        // Sebelumnya unique di kolom non-tenant -> tenant lain kena
        // unique violation saat insert key yang sama (mirip bug settings).

        // members: code + email
        Schema::table('members', function (Blueprint $table) {
            $table->dropUnique('members_code_unique');
            $table->dropUnique('members_email_unique');
        });
        Schema::table('members', function (Blueprint $table) {
            $table->unique(['tenant_id', 'code'], 'members_tenant_id_code_unique');
            $table->unique(['tenant_id', 'email'], 'members_tenant_id_email_unique');
        });

        // barcodes: code
        Schema::table('barcodes', function (Blueprint $table) {
            $table->dropUnique('barcodes_code_unique');
        });
        Schema::table('barcodes', function (Blueprint $table) {
            $table->unique(['tenant_id', 'code'], 'barcodes_tenant_id_code_unique');
        });

        // settlements: (period_start, period_end)
        Schema::table('settlements', function (Blueprint $table) {
            $table->dropUnique('settlements_period_start_period_end_unique');
        });
        Schema::table('settlements', function (Blueprint $table) {
            $table->unique(['tenant_id', 'period_start', 'period_end'], 'settlements_tenant_period_unique');
        });

        // invoices: number
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropUnique('invoices_number_unique');
        });
        Schema::table('invoices', function (Blueprint $table) {
            $table->unique(['tenant_id', 'number'], 'invoices_tenant_id_number_unique');
        });

        // midtrans_payments: order_id
        Schema::table('midtrans_payments', function (Blueprint $table) {
            $table->dropUnique('midtrans_payments_order_id_unique');
        });
        Schema::table('midtrans_payments', function (Blueprint $table) {
            $table->unique(['tenant_id', 'order_id'], 'midtrans_payments_tenant_order_unique');
        });

        // withdrawals: idempotency_key (nullable)
        Schema::table('withdrawals', function (Blueprint $table) {
            $table->dropUnique('withdrawals_idempotency_key_unique');
        });
        Schema::table('withdrawals', function (Blueprint $table) {
            $table->unique(['tenant_id', 'idempotency_key'], 'withdrawals_tenant_idempotency_unique');
        });

        // idempotency_logs: idempotency_key
        Schema::table('idempotency_logs', function (Blueprint $table) {
            $table->dropUnique('idempotency_logs_idempotency_key_unique');
        });
        Schema::table('idempotency_logs', function (Blueprint $table) {
            $table->unique(['tenant_id', 'idempotency_key'], 'idempotency_logs_tenant_key_unique');
        });
    }

    public function down(): void
    {
        // members
        Schema::table('members', function (Blueprint $table) {
            $table->dropUnique('members_tenant_id_code_unique');
            $table->dropUnique('members_tenant_id_email_unique');
        });
        Schema::table('members', function (Blueprint $table) {
            $table->string('code')->unique();
            $table->string('email')->nullable()->unique();
        });

        // barcodes
        Schema::table('barcodes', function (Blueprint $table) {
            $table->dropUnique('barcodes_tenant_id_code_unique');
        });
        Schema::table('barcodes', function (Blueprint $table) {
            $table->string('code')->unique();
        });

        // settlements
        Schema::table('settlements', function (Blueprint $table) {
            $table->dropUnique('settlements_tenant_period_unique');
        });
        Schema::table('settlements', function (Blueprint $table) {
            $table->unique(['period_start', 'period_end']);
        });

        // invoices
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropUnique('invoices_tenant_id_number_unique');
        });
        Schema::table('invoices', function (Blueprint $table) {
            $table->string('number')->unique();
        });

        // midtrans_payments
        Schema::table('midtrans_payments', function (Blueprint $table) {
            $table->dropUnique('midtrans_payments_tenant_order_unique');
        });
        Schema::table('midtrans_payments', function (Blueprint $table) {
            $table->string('order_id')->unique();
        });

        // withdrawals
        Schema::table('withdrawals', function (Blueprint $table) {
            $table->dropUnique('withdrawals_tenant_idempotency_unique');
        });
        Schema::table('withdrawals', function (Blueprint $table) {
            $table->string('idempotency_key')->unique()->index()->nullable();
        });

        // idempotency_logs
        Schema::table('idempotency_logs', function (Blueprint $table) {
            $table->dropUnique('idempotency_logs_tenant_key_unique');
        });
        Schema::table('idempotency_logs', function (Blueprint $table) {
            $table->string('idempotency_key')->unique()->index();
        });
    }
};
