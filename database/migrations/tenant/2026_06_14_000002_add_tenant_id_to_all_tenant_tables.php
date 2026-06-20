<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private array $tables = [
        'abouts', 'barcodes', 'carts', 'cart_items', 'cash_drawers',
        'categories', 'idempotency_logs', 'imports', 'ledger_entries',
        'members', 'midtrans_payments', 'notifications', 'payment_methods',
        'price_units', 'printers', 'products', 'product_images',
        'profiles', 'purchasings', 'receivables', 'receivable_items',
        'receivable_payments',
        'secure_initial_prices',
        'sellings', 'selling_details', 'settings', 'settlements',
        'stocks', 'stock_opnames', 'stock_opname_items', 'suppliers',
        'tables', 'uploaded_files', 'users', 'vouchers', 'withdrawals',
    ];

    public function up(): void
    {
        foreach ($this->tables as $table) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            if (Schema::hasColumn($table, 'tenant_id')) {
                continue;
            }

            Schema::table($table, function (Blueprint $t) use ($table) {
                $t->string('tenant_id')->nullable();
                $t->index('tenant_id');
            });
        }
    }

    public function down(): void
    {
        foreach ($this->tables as $table) {
            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'tenant_id')) {
                continue;
            }

            Schema::table($table, function (Blueprint $t) {
                $t->dropIndex(['tenant_id']);
                $t->dropColumn('tenant_id');
            });
        }
    }
};
