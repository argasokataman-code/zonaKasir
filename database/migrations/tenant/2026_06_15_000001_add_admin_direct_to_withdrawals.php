<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('withdrawals', function (Blueprint $table) {
            // Type: distinguish tenant-initiated vs admin-initiated
            $table->enum('type', ['tenant_request', 'admin_direct'])
                ->default('tenant_request')
                ->after('id');

            // Who initiated (null for tenant_request, admin_id for admin_direct)
            $table->unsignedBigInteger('initiated_by')->nullable()
                ->after('requested_by')
                ->comment('Admin ID who initiated direct transfer');

            // Flip fee snapshot
            $table->decimal('fee_amount', 15, 2)->nullable()
                ->after('amount')
                ->comment('Flip fee charged for this transfer');

            // Internal notes (admin only, not visible to tenant)
            $table->text('internal_notes')->nullable()
                ->after('notes')
                ->comment('Internal notes, not visible to tenant');

            // Indexes for performance
            $table->index('type');
            $table->index('initiated_by');
        });
    }

    public function down(): void
    {
        Schema::table('withdrawals', function (Blueprint $table) {
            $table->dropIndex(['type']);
            $table->dropIndex(['initiated_by']);
            $table->dropColumn(['type', 'initiated_by', 'fee_amount', 'internal_notes']);
        });
    }
};
