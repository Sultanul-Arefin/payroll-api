<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Designation\Entities\Designation;

class DesignationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Designation::create([
            'company_id' => 1,
            'name' => 'Jr Developer',
        ]);
        Designation::create([
            'company_id' => 1,
            'name' => 'Sr Developer',
        ]);
    }
}
