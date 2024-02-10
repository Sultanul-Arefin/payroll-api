<?php

namespace Database\Seeders;

use App\Models\Admin;
use Illuminate\Database\Seeder;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $user = Admin::create([
            'email' => 'admin@bfin.it',
            'name' => 'Admin',
            'password' => bcrypt('password'), // password
        ]);
    }
}
