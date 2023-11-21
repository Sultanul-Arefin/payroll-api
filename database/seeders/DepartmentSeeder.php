<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Department\Entities\Department;

class DepartmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Department::create([
            'department_name' => 'SD Department',
            'company_id' => 1,
        ]);
        Department::create([
            'department_name' => 'IT Department',
            'company_id' => 1,
        ]);
    }
}
