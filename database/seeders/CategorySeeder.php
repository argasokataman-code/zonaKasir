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
        $driver = DB::getDriverName();
        if ($driver === 'mysql') {
            DB::statement('SET FOREIGN_KEY_CHECKS=0');
        } elseif ($driver === 'pgsql') {
            DB::statement('SET CONSTRAINTS ALL DISABLE');
        }
        Category::truncate();
        if ($driver === 'pgsql') {
            DB::statement('SET CONSTRAINTS ALL ENABLE');
        }
        Category::create([
            'name' => "UMUM"
        ]);
    }
}
