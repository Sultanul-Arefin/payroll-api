<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;
use Modules\Company\Entities\Company;
use Modules\Department\Entities\Department;
use Modules\User\Entities\UserDetails;

class DepartmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Department::create([
            'department_name' => 'SD Department',
            'company_id' => 1
        ]);
        Department::create([
            'department_name' => 'IT Department',
            'company_id' => 1
        ]);
    }
}