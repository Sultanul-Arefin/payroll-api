<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;
use Modules\Company\Entities\Company;
use Modules\Designation\Entities\Designation;
use Modules\User\Entities\UserDetails;

class DesignationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Designation::create([
            'company_id' => 1,
            'name' => 'Jr Developer'
        ]);
        Designation::create([
            'company_id' => 1,
            'name' => 'Sr Developer'
        ]);
    }
}