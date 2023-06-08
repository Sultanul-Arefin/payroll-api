<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;
use Modules\Company\Entities\Company;
use Modules\User\Entities\UserDetails;

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
            'company_id' => Company::factory()
        ]);
        UserDetails::create([
            'user_id' => $user->id,
            'user_city' => 'Dhaka, Bangladesh',
            'user_phone' => '1234567890',
            // 'user_image' => $request->user_address ?? null,
        ]);
        $user->assignRole('super-admin');
        // User::factory()->count(10)->create();
    }
}