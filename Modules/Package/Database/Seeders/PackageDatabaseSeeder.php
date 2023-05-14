<?php

namespace Modules\Package\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;
use Modules\Package\Entities\Package;

class PackageDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Model::unguard();

        // Package::factory()->count(10)->create();
        Package::create([
            'package_name' => 'Payroll HR 50',
            'no_of_departments' => 50,
            'no_of_employees' => 50,
            'no_of_payslips' => 2600
        ]);
        Package::create([
            'package_name' => 'Payroll HR 75',
            'no_of_departments' => 50,
            'no_of_employees' => 75,
            'no_of_payslips' => 3900
        ]);
        Package::create([
            'package_name' => 'Payroll HR 150',
            'no_of_departments' => 50,
            'no_of_employees' => 150,
            'no_of_payslips' => 7800
        ]);
        Package::create([
            'package_name' => 'Payroll HR 300',
            'no_of_departments' => 50,
            'no_of_employees' => 300,
            'no_of_payslips' => 15600
        ]);
        Package::create([
            'package_name' => 'Payroll HR 500',
            'no_of_departments' => 50,
            'no_of_employees' => 500,
            'no_of_payslips' => 26000
        ]);
    }
}
