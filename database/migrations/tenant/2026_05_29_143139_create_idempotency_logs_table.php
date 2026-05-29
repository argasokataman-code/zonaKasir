<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('idempotency_logs', function (Blueprint $table) {
            $table->id();
            $table->string('idempotency_key')->unique()->index(); // Client-provided unique key
            $table->enum('status', ['processing', 'completed', 'failed'])->default('processing');
            $table->string('endpoint'); // API endpoint that was called
            $table->string('method'); // HTTP method (POST, PUT, etc)
            $table->longText('response')->nullable(); // JSON response or error
            $table->timestamp('expires_at')->nullable(); // When this log can be cleaned up
            $table->timestamps();
            
            // Index for cleanup queries
            $table->index(['created_at', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('idempotency_logs');
    }
};
