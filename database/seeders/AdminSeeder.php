<?php

namespace Database\Seeders;

use App\Models\Admin;
use Illuminate\Database\Seeder;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        if (Admin::where('email', 'superadmin@admin.com')->doesntExist()) {
            Admin::create([
                'name' => 'Super Admin',
                'email' => 'superadmin@admin.com',
                'password' => bcrypt('password'),
            ]);
        }
    }
}
