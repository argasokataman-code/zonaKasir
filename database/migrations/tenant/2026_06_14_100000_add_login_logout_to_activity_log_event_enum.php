<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE activity_log MODIFY COLUMN event ENUM('created', 'updated', 'deleted', 'login', 'logout') NULL");
        } elseif ($driver === 'sqlite') {
            DB::statement("ALTER TABLE activity_log ADD COLUMN event_new TEXT NULL");
            DB::statement("UPDATE activity_log SET event_new = event");
            DB::statement("ALTER TABLE activity_log DROP COLUMN event");
            DB::statement("ALTER TABLE activity_log RENAME COLUMN event_new TO event");
        }
    }

    public function down(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE activity_log MODIFY COLUMN event ENUM('created', 'updated', 'deleted') NULL");
        } elseif ($driver === 'sqlite') {
            // SQLite doesn't support drop column in older versions
            // Recreate is too destructive, just leave it
        }
    }
};
