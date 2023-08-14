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
                'Wages',
                'Ordinary Time Rate',
                'Maternity Time Rate',
                'Paid Sick Leave Rate',
                'Unpaid Sick Leave Rate',
                'Holiday Rate',
                'Absent',
                'Bonus',
                'Overtime Rate',
                'Double Overtime Rate',
                'Recuperated Hour',
            ],
            [
                'Annual Leave',
                'Sick Leave',
                'Absent',
                'Unpaid Sick Leave'
            ],
            [
                'Taxable Allowance 1',
                'Taxable Allowance 2'
            ],
            [
                'Non Taxable Allowance 1',
                'Non Taxable Allowance 2'
            ],
            [
                'Income Taxes 1',
                'Income Taxes 2'
            ],
            [
                'Additional Taxes(Tax TopUp) 1',
                'Additional Taxes(Tax TopUp) 2',
            ],
            [
                'Government Deductions 1',
                'Government Deductions 2'
            ],
            [
                'Other Complimentary Deductions 1',
                'Other Complimentary Deductions 2'
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
