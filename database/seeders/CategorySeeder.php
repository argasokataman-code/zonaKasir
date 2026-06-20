<?php

namespace Database\Seeders;

use App\Models\Tenants\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('SET session_replication_role = replica');
            Category::truncate();
            DB::statement('SET session_replication_role = origin');
        } else {
            DB::statement('SET FOREIGN_KEY_CHECKS=0');
            Category::truncate();
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
        }
        Category::create([
            'name' => "UMUM"
        ]);
    }
}
