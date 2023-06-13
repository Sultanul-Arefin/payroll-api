<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Modules\SalaryItemsName\Entities\SalaryItemsName;

class SalaryItemsNamesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            [
                'Ordinary Time',
                'Annual Leave',
                'Sick Leave',
            ],
            [
                'Absent',
                'Unpaid Sick Leave'
            ],
            [
                'Item 1'
            ],
            'Non-Taxable Allowances',
            'Income Taxes',
            'Additional Taxes(Tax TopUp)',
            'Government Deductions',
            'Other Complimentary Deductions'
        ];
        foreach($categories as $key => $value){
            if(is_array($value)){
                foreach($value as $item){
                    SalaryItemsName::create([
                        'salary_items_category_id' => $key + 1,
                        'name' => $item,
                        'company_id' => 1
                    ]);
                }
            }
        }
    }
}
