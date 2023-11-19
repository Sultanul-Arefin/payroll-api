<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;
use Modules\Company\Entities\Company;
use Modules\Company\Entities\CompanyAssociatedWithPackage;
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
            'password' => 'password', // password
            'company_id' => Company::factory(),
            'role_id' => User::ADMIN,
            'employee_type' => User::EMPLOYEE_TYPE_FULL_TIME
        ]);
        UserDetails::create([
            'user_id' => $user->id,
            'user_city' => 'Dhaka, Bangladesh',
            'user_phone' => '1234567890',
            'country_id' => 1
            // 'user_image' => $request->user_address ?? null,
        ]);
        CompanyAssociatedWithPackage::create([
            'company_id' => 1,
            'package_id' => 1
        ]);
        /** User 1 */
        $user = User::factory()->create([
            'email' => 'fahimsultan@bfin.it',
            'name' => 'FahimSultan',
            'email_verified_at' => now(),
            'status' => User::USER_ACTIVE,
            'password' => 'password', // password
            'company_id' => 1,
            'role_id' => User::DEPARTMENT_MANAGER,
            'employee_type' => User::EMPLOYEE_TYPE_FULL_TIME
        ]);
        UserDetails::create([
            'user_id' => $user->id,
            'user_city' => 'Dhaka, Bangladesh',
            'user_phone' => '1234567890',
            'country_id' => 2
            // 'user_image' => $request->user_address ?? null,
        ]);
        CompanyAssociatedWithPackage::create([
            'company_id' => 1,
            'package_id' => 1
        ]);
        /** User 2 */
        $user = User::factory()->create([
            'email' => 'arefin@bfin.it',
            'name' => 'Arefin',
            'email_verified_at' => now(),
            'status' => User::USER_ACTIVE,
            'password' => 'password', // password
            'company_id' => 1,
            'role_id' => User::EMPLOYEE,
            'employee_type' => User::EMPLOYEE_TYPE_FULL_TIME
        ]);
        UserDetails::create([
            'user_id' => $user->id,
            'user_city' => 'Dhaka, Bangladesh',
            'user_phone' => '1234567890',
            'country_id' => 3
            // 'user_image' => $request->user_address ?? null,
        ]);
        CompanyAssociatedWithPackage::create([
            'company_id' => 1,
            'package_id' => 1
        ]);
        // User::factory()->count(10)->create();
    }
}