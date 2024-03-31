<?php

namespace Modules\EmployeeSalaryItems\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Modules\EmployeeSalaryItems\Entities\DeductionDetails;
use Modules\EmployeeSalaryItems\Entities\EmployeeSalaryItem;
use Modules\EmployeeSalaryItems\Http\Requests\StoreEmployeeSalaryItems;
use Modules\EmployeeSalaryItems\Http\Resources\DetailsWagesAgainstCategoryResource;
use Modules\EmployeeSalaryItems\Http\Services\SalaryItemsCategoryService;
use Modules\SalaryItemsName\Entities\SalaryItemsName;

class EmployeeSalaryItemsController extends Controller
{
    public function __construct(
        private SalaryItemsCategoryService $salaryItemsCategoryService
    ) {
    }
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

    public function getSalaryItemsValue(Request $request)
    {
        $request->validate([
            'salary_items_category_id' => 'required|exists:salary_items_categories,id',
        ]);
        $rows = 15;
        if (request()?->has('rows')) {
            $rows = (int) request('rows');
        }

        return DetailsWagesAgainstCategoryResource::collection(
            $this->salaryItemsCategoryService->get_salary_items_name_with_employee(
                $request->salary_items_category_id, $rows
            )
        );
    }

    public function update_salary_item(EmployeeSalaryItem $employee_salary_item, Request $request)
    {
        $request->validate([
            'amount' => 'required',
            '_method' => 'required'
        ]);
        $employee_salary_item->update([
            'amount' => $request->amount
        ]);
        return apiResponse(
            data: $employee_salary_item,
            message: 'Salary Amount Updated Successfully'
        );
    }

    public function delete_salary_item(EmployeeSalaryItem $employee_salary_item, Request $request)
    {
        $request->validate([
            '_method' => 'required'
        ]);
        $employee_salary_item->delete();
        return apiResponse(
            data: [],
            message: 'Salary Amount Deleted Successfully'
        );
    }
}
