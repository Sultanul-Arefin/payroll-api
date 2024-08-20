<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
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
        Company::create([
            'company_name' => 'Test',
            'company_address' => 'Test',
            'company_email' => 'test@test.com',
            'company_phone' => 12231,
            'company_logo' => null,
            'company_website' => null,
            'company_registration_no' => null,
            'government_employee_no' => null,
            'fiscal_year_from' => null,
            'fiscal_year_to' => null,
            'bank_name' => null,
            'bank_bic_or_swift_code' => null,
            'bank_iban_or_account_no' => null,
            'contact_person_name' => null,
            'contact_person_email' => null,
            'contact_person_phone' => null,
            'no_of_working_days_per_week' => null,
            'working_hours_per_day' => null,
            'lunch_and_others_per_day' => null,
            'working_hours_per_week' => null,
            'white_label_id' => 1
        ]);
        $user = User::factory()->create([
            'email' => 'admin@bfin.it',
            'name' => 'Admin',
            'email_verified_at' => now(),
            'status' => User::USER_ACTIVE,
            'staff_interaction_panel_status' => User::STAFF_INTERACTION_PANEL_GIVEN,
            'password' => 'password', // password
            // 'company_id' => Company::factory(),
            'company_id' => 1,
            'role_id' => User::ADMIN,
            'employee_type' => User::EMPLOYEE_TYPE_FULL_TIME,
        ]);
        UserDetails::create([
            'user_id' => $user->id,
            'user_city' => 'Dhaka, Bangladesh',
            'user_phone' => '1234567890',
            'country_id' => 1,
            // 'user_image' => $request->user_address ?? null,
        ]);
        CompanyAssociatedWithPackage::create([
            'company_id' => 1,
            'package_id' => 4,
        ]);
        /** User 1 */
        $user = User::factory()->create([
            'email' => 'fahimsultan@bfin.it',
            'name' => 'FahimSultan',
            'email_verified_at' => now(),
            'status' => User::USER_ACTIVE,
            'staff_interaction_panel_status' => User::STAFF_INTERACTION_PANEL_GIVEN,
            'password' => 'password', // password
            'company_id' => 1,
            'role_id' => User::DEPARTMENT_MANAGER,
            'employee_type' => User::EMPLOYEE_TYPE_FULL_TIME,
        ]);
        UserDetails::create([
            'user_id' => $user->id,
            'user_city' => 'Dhaka, Bangladesh',
            'user_phone' => '1234567890',
            'country_id' => 2,
            // 'user_image' => $request->user_address ?? null,
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
            'employee_type' => User::EMPLOYEE_TYPE_FULL_TIME,
        ]);
        UserDetails::create([
            'user_id' => $user->id,
            'user_city' => 'Dhaka, Bangladesh',
            'user_phone' => '1234567890',
            'country_id' => 3,
            // 'user_image' => $request->user_address ?? null,
        ]);
        // User::factory()->count(10)->create();
    }
}
