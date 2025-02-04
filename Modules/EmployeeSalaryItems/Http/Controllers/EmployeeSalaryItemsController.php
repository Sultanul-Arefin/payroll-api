<?php

namespace Modules\EmployeeSalaryItems\Http\Controllers;

use App\Models\ThresholdDetails;
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
        // validation for category_id 5
        if ($check_category->salary_items_category_id == 5) {
            $request->validate([
                'tax_type' => 'required|in:straight,threshold',
                'start_percentage_after' => 'required_if:tax_type,threshold',
                'end_percentage_at' => 'nullable'
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
            if ($check_category->salary_items_category_id == 5 && $request->tax_type == "threshold") {
                $create_deduction = ThresholdDetails::create([
                    'employee_salary_item_id' => $employee_salary->id,
                    'start_percentage_after' => $request->start_percentage_after,
                    'end_percentage_at' => $request->end_percentage_at,
                ]);
            }
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
            // 'amount' => 'required',
            '_method' => 'required',
            'is_percentage' => 'required',
        ]);
        if($employee_salary_item?->salaryItemsName?->salary_items_category_id == 7)
        {
            $employee_salary_item?->deduction_details?->update([
                'employee_amount' => $request->employee_contribution,
                'government_or_company_amount' => $request->company_deduction
            ]);
        } elseif($employee_salary_item?->salaryItemsName?->salary_items_category_id == 8)
        {
            $employee_salary_item?->deduction_details?->update([
                'employee_amount' => $request->employee_deduction,
                'government_or_company_amount' => $request->company_deduction
            ]);
        } elseif($employee_salary_item?->salaryItemsName?->salary_items_category_id == 5 && $employee_salary_item?->salaryItemsName?->is_threshold == 2){
            $employee_salary_item?->threshold_details?->update([
                'start_percentage_after' => $request->start_percentage_after,
                'end_percentage_at' => $request->end_percentage_at
            ]);
            $employee_salary_item->update([
                'amount' => $request->amount
            ]);
        } else{
            $employee_salary_item->update([
                'amount' => $request->amount ?? $employee_salary_item->amount
            ]);
        }
        // UPDATE OTHER INFORMATION
        $employee_salary_item->update([
            'is_percentage' => ($request->is_percentage && $request->is_percentage == "true") ? 1 : 0,
            'is_general' => $request->is_general ?? $employee_salary_item->is_general,
            'employee_id' => ($request->employee_id &&  $request->employee_id != "null") ? $request->employee_id : $employee_salary_item->employee_id
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
