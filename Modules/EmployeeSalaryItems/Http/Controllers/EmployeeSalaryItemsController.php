<?php

namespace Modules\EmployeeSalaryItems\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Modules\EmployeeSalaryItems\Entities\DeductionDetails;
use Modules\EmployeeSalaryItems\Entities\EmployeeSalaryItem;
use Modules\EmployeeSalaryItems\Http\Requests\StoreEmployeeSalaryItems;
use Modules\SalaryItemsName\Entities\SalaryItemsName;

class EmployeeSalaryItemsController extends Controller
{
    public function store(StoreEmployeeSalaryItems $request)
    {
        // validation for category_id 7 & 8
        $check_category = SalaryItemsName::where('id', $request->salary_item_id)->first();
        if ($check_category->salary_items_category_id == 7 || $check_category->salary_items_category_id == 8) {
            $request->validate([
                'company_deduction' => 'required', // '1|2'
                'employee_contribution' => $check_category->salary_items_category_id == 7 ? 'required' : 'nullable',
                'employee_deduction' => $check_category->salary_items_category_id == 8 ? 'required' : 'nullable', // 1|3
                'amount' => 'nullable',
            ]);
        }
        $employee_salary = DB::transaction(function () use ($request, $check_category) {
            $employee_salary = EmployeeSalaryItem::create([
                'salary_item_id' => $request->salary_item_id,
                'company_id' => auth()->user()->company_id,
                'is_percentage' => $request->is_percentage,
                'is_general' => $request->is_general,
                'employee_id' => $request->employee_id ?? null,
                'amount' => $request->amount ?? null,
            ]);
            if ($check_category->salary_items_category_id == 7 || $check_category->salary_items_category_id == 8) {
                $create_deduction = DeductionDetails::create([
                    'employee_salary_item_id' => $employee_salary->id,
                    'employee_amount' => $check_category->salary_items_category_id == 7 ? $request->employee_contribution : $request->employee_deduction,
                    'government_or_company_amount' => $request->company_deduction,
                ]);
            }
            return $employee_salary;
        });
        return apiResponse(
            data: null,
            message: 'Salary Items Associate Successfully',
            status: 'success'
        );
    }
}
