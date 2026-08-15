<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('onprem_instances', function (Blueprint $table) {
            $table->id();
            $table->string('instance_id')->unique();
            $table->string('domain')->nullable();
            $table->string('customer_name')->nullable();
            $table->string('license_key')->nullable()->unique();
            $table->string('app_version')->nullable();
            $table->string('php_version')->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('onprem_instances');
    }
};
