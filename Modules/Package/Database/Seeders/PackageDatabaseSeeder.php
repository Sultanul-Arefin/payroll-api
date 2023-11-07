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
            'id' => 1,
            'package_name' => 'Payroll Basic 5',
            'base_price' => 5.00,
            'discounted_price' => 5.00,
            'currency' => 'EUR',
            'price_charged' => Package::PRICE_CHARGED_1,
            'no_of_employees' => 5,
            'no_of_payslips' => 260
        ]);
        Package::create([
            'id' => 2,
            'package_name' => 'Payroll 10',
            'base_price' => 7.00,
            'discounted_price' => 7.00,
            'currency' => 'EUR',
            'price_charged' => Package::PRICE_CHARGED_1,
            'no_of_employees' => 10,
            'no_of_payslips' => 520
        ]);
        Package::create([
            'id' => 3,
            'package_name' => 'Payroll 25',
            'base_price' => 10.00,
            'discounted_price' => 10.00,
            'currency' => 'EUR',
            'price_charged' => Package::PRICE_CHARGED_1,
            'no_of_employees' => 25,
            'no_of_payslips' => 1300
        ]);
        Package::create([
            'id' => 4,
            'package_name' => 'Payroll HR 50',
            'base_price' => 40.00,
            'discounted_price' => 40.00,
            'currency' => 'EUR',
            'price_charged' => Package::PRICE_CHARGED_1,
            'no_of_employees' => 50,
            'no_of_payslips' => 2600
        ]);
        Package::create([
            'id' => 5,
            'package_name' => 'Payroll HR 75',
            'base_price' => 50.00,
            'discounted_price' => 50.00,
            'currency' => 'EUR',
            'price_charged' => Package::PRICE_CHARGED_1,
            'no_of_employees' => 75,
            'no_of_payslips' => 3900
        ]);
        Package::create([
            'id' => 6,
            'package_name' => 'Payroll HR 150',
            'base_price' => 120.00,
            'discounted_price' => 120.00,
            'currency' => 'EUR',
            'price_charged' => Package::PRICE_CHARGED_1,
            'no_of_employees' => 150,
            'no_of_payslips' => 7800
        ]);
        Package::create([
            'id' => 7,
            'package_name' => 'Payroll HR 300',
            'base_price' => 200.00,
            'discounted_price' => 200.00,
            'currency' => 'EUR',
            'price_charged' => Package::PRICE_CHARGED_1,
            'no_of_employees' => 300,
            'no_of_payslips' => 15600
        ]);
        Package::create([
            'id' => 8,
            'package_name' => 'Payroll HR 500',
            'base_price' => 250.00,
            'discounted_price' => 250.00,
            'currency' => 'EUR',
            'price_charged' => Package::PRICE_CHARGED_1,
            'no_of_employees' => 500,
            'no_of_payslips' => 26000
        ]);
        Package::create([
            'id' => 9,
            'package_name' => 'Easy Payroll',
            'base_price' => 3.75,
            'discounted_price' => 3.75,
            'currency' => 'EUR',
            'price_charged' => Package::PRICE_CHARGED_1,
            'no_of_employees' => 10,
            'no_of_payslips' => 60
        ]);
        Package::create([
            'id' => 10,
            'package_name' => 'Easy Micro Payroll',
            'base_price' => 4.75,
            'discounted_price' => 4.75,
            'currency' => 'EUR',
            'price_charged' => Package::PRICE_CHARGED_1,
            'no_of_employees' => 12,
            'no_of_payslips' => 48
        ]);
        Package::create([
            'id' => 11,
            'package_name' => 'Easy Micro Payroll+',
            'base_price' => 5.75,
            'discounted_price' => 5.75,
            'currency' => 'EUR',
            'price_charged' => Package::PRICE_CHARGED_1,
            'no_of_employees' => 14,
            'no_of_payslips' => 74
        ]);
        Package::create([
            'id' => 14,
            'package_name' => 'Basic Payroll 2',
            'base_price' => 0.00,
            'discounted_price' => 0.00,
            'currency' => 'EUR',
            'price_charged' => Package::PRICE_CHARGED_1,
            'no_of_employees' => 2,
            'no_of_payslips' => 24
        ]);
        Package::create([
            'id' => 15,
            'package_name' => 'Payroll Basic 50',
            'base_price' => 15.00,
            'discounted_price' => 15.00,
            'currency' => 'EUR',
            'price_charged' => Package::PRICE_CHARGED_1,
            'no_of_employees' => 50,
            'no_of_payslips' => 2600
        ]);
        Package::create([
            'id' => 16,
            'package_name' => 'Payroll Basic 100',
            'base_price' => 30.00,
            'discounted_price' => 30.00,
            'currency' => 'EUR',
            'price_charged' => Package::PRICE_CHARGED_1,
            'no_of_employees' => 100,
            'no_of_payslips' => 5200
        ]);
        Package::create([
            'id' => 17,
            'package_name' => 'Payroll Basic 150',
            'base_price' => 45.00,
            'discounted_price' => 45.00,
            'currency' => 'EUR',
            'price_charged' => Package::PRICE_CHARGED_1,
            'no_of_employees' => 150,
            'no_of_payslips' => 7800
        ]);
        Package::create([
            'id' => 18,
            'package_name' => 'Payroll Basic 200',
            'base_price' => 60.00,
            'discounted_price' => 60.00,
            'currency' => 'EUR',
            'price_charged' => Package::PRICE_CHARGED_1,
            'no_of_employees' => 200,
            'no_of_payslips' => 10400
        ]);
        Package::create([
            'id' => 19,
            'package_name' => 'Payroll Basic 300',
            'base_price' => 80.00,
            'discounted_price' => 80.00,
            'currency' => 'EUR',
            'price_charged' => Package::PRICE_CHARGED_1,
            'no_of_employees' => 300,
            'no_of_payslips' => 15600
        ]);
        Package::create([
            'id' => 20,
            'package_name' => 'Payroll Basic 500',
            'base_price' => 110.00,
            'discounted_price' => 110.00,
            'currency' => 'EUR',
            'price_charged' => Package::PRICE_CHARGED_1,
            'no_of_employees' => 500,
            'no_of_payslips' => 26000
        ]);
        Package::create([
            'id' => 21,
            'package_name' => 'Payroll Basic 3',
            'base_price' => 2.95,
            'discounted_price' => 2.95,
            'currency' => 'EUR',
            'price_charged' => Package::PRICE_CHARGED_1,
            'no_of_employees' => 8,
            'no_of_payslips' => 48
        ]);
        Package::create([
            'id' => 22,
            'package_name' => 'Payroll Basic 4',
            'base_price' => 4.95,
            'discounted_price' => 4.95,
            'currency' => 'EUR',
            'price_charged' => Package::PRICE_CHARGED_1,
            'no_of_employees' => 9,
            'no_of_payslips' => 54
        ]);
        Package::create([
            'id' => 23,
            'package_name' => 'Project & Task Management 50',
            'base_price' => 14.98,
            'discounted_price' => 14.98,
            'currency' => 'EUR',
            'price_charged' => Package::PRICE_CHARGED_1,
            'no_of_employees' => 50,
            'no_of_payslips' => 0
        ]);
        Package::create([
            'id' => 24,
            'package_name' => 'Project & Task Management 75',
            'base_price' => 29.98,
            'discounted_price' => 29.98,
            'currency' => 'EUR',
            'price_charged' => Package::PRICE_CHARGED_1,
            'no_of_employees' => 75,
            'no_of_payslips' => 0
        ]);
        Package::create([
            'id' => 25,
            'package_name' => 'Project & Task Management 150',
            'base_price' => 74.93,
            'discounted_price' => 74.93,
            'currency' => 'EUR',
            'price_charged' => Package::PRICE_CHARGED_1,
            'no_of_employees' => 150,
            'no_of_payslips' => 0
        ]);
        Package::create([
            'id' => 26,
            'package_name' => 'Project & Task Management 300',
            'base_price' => 104.90,
            'discounted_price' => 104.90,
            'currency' => 'EUR',
            'price_charged' => Package::PRICE_CHARGED_1,
            'no_of_employees' => 300,
            'no_of_payslips' => 0
        ]);
        Package::create([
            'id' => 27,
            'package_name' => 'Project & Task Management 500',
            'base_price' => 134.86,
            'discounted_price' => 134.86,
            'currency' => 'EUR',
            'price_charged' => Package::PRICE_CHARGED_1,
            'no_of_employees' => 500,
            'no_of_payslips' => 0
        ]);
        Package::create([
            'id' => 29,
            'package_name' => 'Nanny. D Payroll HR 5',
            'base_price' => 10.00,
            'discounted_price' => 10.00,
            'currency' => 'EUR',
            'price_charged' => Package::PRICE_CHARGED_1,
            'no_of_employees' => 50,
            'no_of_payslips' => 2600
        ]);
        Package::create([
            'id' => 30,
            'package_name' => 'Payroll HR 10',
            'base_price' => 15.00,
            'discounted_price' => 15.00,
            'currency' => 'EUR',
            'price_charged' => Package::PRICE_CHARGED_1,
            'no_of_employees' => 75,
            'no_of_payslips' => 3900
        ]);
        Package::create([
            'id' => 31,
            'package_name' => 'Payroll HR 25',
            'base_price' => 25.00,
            'discounted_price' => 25.00,
            'currency' => 'EUR',
            'price_charged' => Package::PRICE_CHARGED_1,
            'no_of_employees' => 150,
            'no_of_payslips' => 7800
        ]);
    }
}
