<?php

namespace Modules\Auth\Http\Services;

use Modules\Department\Entities\Department;
use Modules\Designation\Entities\Designation;
use Modules\SalaryItemsName\Entities\LeaveSalaryItems;
use Modules\SalaryItemsName\Entities\SalaryItemsName;

class UserServices
{
    // salary items name seeder
    public function salary_items_name_seeder($company_id)
    {
        $categories = [
            [
                'Wages',
                'Ordinary Time Rate',
                'Maternity Time Rate',
                'Paid Sick Leave Rate',
                'Unpaid Sick Leave Rate',
                'Holiday Rate',
                'Absent Rate',
                'Bonus',
                'Overtime Rate',
                'Double Overtime Rate',
                'Recuperated Hour',
            ],
            [
                'Annual Leave',
                'Sick Leave',
                'Absent',
                'Unpaid Sick Leave',
            ],
            [
                'Taxable Allowance 1',
                'Taxable Allowance 2',
            ],
            [
                'Non Taxable Allowance 1',
                'Non Taxable Allowance 2',
            ],
            [
                'Income Taxes 1',
                'Income Taxes 2',
            ],
            [
                'Additional Taxes(Tax TopUp) 1',
                'Additional Taxes(Tax TopUp) 2',
            ],
            [
                'Government Deductions 1',
                'Government Deductions 2',
            ],
            [
                'Other Complimentary Deductions 1',
                'Other Complimentary Deductions 2',
            ],
            'Non-Taxable Allowances',
            'Income Taxes',
            'Additional Taxes(Tax TopUp)',
            'Government Deductions',
            'Other Complimentary Deductions',
        ];
        foreach ($categories as $key => $value) {
            if (is_array($value)) {
                foreach ($value as $item) {
                    SalaryItemsName::create([
                        'salary_items_category_id' => $key + 1,
                        'name' => $item,
                        'company_id' => $company_id,
                    ]);
                }
            }
        }
    }

    // leave salary items
    public function leave_salary_items($company_id)
    {
        $salary_items_names = SalaryItemsName::where('company_id', $company_id)->where('salary_items_category_id', 2)->get();
        foreach ($salary_items_names as $value) {
            LeaveSalaryItems::create([
                'salary_items_id' => $value->id,
                'no_of_days' => 0,
            ]);
        }
    }

    // department seeder
    public function department_seeder($company_id)
    {
        Department::create([
            'department_name' => 'Software Department',
            'company_id' => $company_id,
        ]);
        Department::create([
            'department_name' => 'IT Department',
            'company_id' => $company_id,
        ]);
    }

    // designation seeder
    public function designation_seeder($company_id)
    {
        Designation::create([
            'company_id' => $company_id,
            'name' => 'Jr Developer',
        ]);
        Designation::create([
            'company_id' => $company_id,
            'name' => 'Sr Developer',
        ]);
    }
}
