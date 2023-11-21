<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\SalaryItemsCategory\Entities\SalaryItemsCategory;

class SalaryItemsCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            'Wages',
            'Staff Deductions(Unpaid/Absent)',
            'Taxable Allowances',
            'Non-Taxable Allowances',
            'Income Taxes',
            'Additional Taxes(Tax TopUp)',
            'Government Deductions',
            'Other Complimentary Deductions',
        ];
        foreach ($categories as $value) {
            SalaryItemsCategory::create([
                'name' => $value,
            ]);
        }
    }
}
