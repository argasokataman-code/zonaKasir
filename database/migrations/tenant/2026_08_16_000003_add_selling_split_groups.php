<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // FR-3: split bill. selling_split_groups = 1 baris per bagian tagihan.
        Schema::create('selling_split_groups', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id');
            $table->foreignId('selling_id')->constrained()->onDelete('cascade');
            $table->string('label', 50)->nullable()
                ->comment('nama bagian, misal "Bagian 1" / nama tamu');
            $table->string('status', 20)->default('pending')
                ->comment('pending, paid');
            $table->double('subtotal')->default(0);
            $table->double('tax_amount')->default(0);
            $table->double('discount_amount')->default(0);
            $table->double('total')->default(0);
            $table->double('paid_total')->default(0)
                ->comment('Σ selling_payments success utk group ini');
            $table->timestamps();

            $table->index(['tenant_id', 'selling_id', 'status']);
        });

        Schema::table('selling_details', function (Blueprint $table) {
            $table->foreignId('split_group_id')->nullable()->constrained('selling_split_groups')
                ->nullOnDelete()->after('selling_id');
        });

        Schema::table('selling_payments', function (Blueprint $table) {
            $table->foreignId('split_group_id')->nullable()->constrained('selling_split_groups')
                ->nullOnDelete()->after('selling_id');
        });
    }

    public function down(): void
    {
        Schema::table('selling_payments', function (Blueprint $table) {
            $table->dropForeign(['split_group_id']);
            $table->dropColumn('split_group_id');
        });
        Schema::table('selling_details', function (Blueprint $table) {
            $table->dropForeign(['split_group_id']);
            $table->dropColumn('split_group_id');
        });
        Schema::dropIfExists('selling_split_groups');
    }
};
