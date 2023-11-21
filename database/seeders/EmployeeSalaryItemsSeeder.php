<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\EmployeeSalaryItems\Entities\EmployeeSalaryItem;
use Modules\SalaryItemsName\Entities\SalaryItemsName;

class EmployeeSalaryItemsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $salary_items_names = SalaryItemsName::where('company_id', 1)->get();
        foreach ($salary_items_names as $value) {
            EmployeeSalaryItem::create([
                'salary_item_id' => $value->id,
                'employee_id' => 1,
                'company_id' => 1,
                'is_percentage' => 0,
                'is_general' => 0,
                'amount' => 100,
            ]);
        }
    }
}
