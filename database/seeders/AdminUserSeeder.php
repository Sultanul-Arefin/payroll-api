<?php

namespace Database\Seeders;

use App\Models\Admin;
use App\Models\WhiteLabelPartner;
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

        $white_label = WhiteLabelPartner::create([
            'name' => 'Admin',
            'email' => 'admin@bfin.it',
            'mobile' => 111,
            'fax' => 111,
            'country_id' => 1,
            'address' => 'Test',
            'contact_person_name' => 'Admin',
            'contact_person_email' => 'admin@bfin.it',
            'contact_person_number' => 111,
            'password' => bcrypt('password')
        ]);
    }
}
