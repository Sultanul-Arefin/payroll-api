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
            'Wages' => [
                'Ordinary Time',
                'Annual Leave',
                'Sick Leave',
            ],
            'Staff Deductions(Unpaid/Absent)' => [
                'Absent',
                'Unpaid Sick Leave'
            ],
            'Taxable Allowances' => [
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
                        'salary_items_category_id' => $key,
                        'name' => $item
                    ]);
                }
            }
        }
    }
}
