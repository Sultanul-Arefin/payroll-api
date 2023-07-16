<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Modules\EmployeeSalaryItems\Entities\EmployeeSalaryItem;
use Modules\SalaryItemsName\Entities\LeaveSalaryItems;
use Modules\SalaryItemsName\Entities\SalaryItemsName;

class LeaveSalaryItemsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $salary_items_names = SalaryItemsName::where('company_id', 1)->where('salary_items_category_id')->get();
        foreach($salary_items_names as $value){
            LeaveSalaryItems::create([
                'salary_item_id' => $value->id,
                'no_of_days' => 0,
            ]);
        }
    }
}
