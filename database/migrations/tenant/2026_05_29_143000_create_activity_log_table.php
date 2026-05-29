<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('activity_log', function (Blueprint $table) {
            $table->id();
            $table->string('log_name')->nullable();
            $table->text('description')->nullable();
            $table->nullableMorphs('subject', 'subject');
            $table->nullableMorphs('causer', 'causer');
            $table->json('properties')->nullable();
            $table->uuid('batch_uuid')->nullable();
            $table->enum('event', ['created', 'updated', 'deleted'])->nullable();
            $table->timestamps();
            $table->index('log_name');
            $table->index('batch_uuid');
        });
    }

    public function down()
    {
        Schema::dropIfExists('activity_log');
    }
};
