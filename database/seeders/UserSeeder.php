<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $user = User::factory()->create([
            'email' => 'admin@bfin.it',
            'name' => 'Admin',
            'email_verified_at' => now(),
            'status' => User::USER_ACTIVE,
            'password' => bcrypt('password'), // password
        ]);
        User::factory()->count(10)->create();
    }
}
