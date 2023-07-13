<?php

namespace Modules\Payslip\Http\Services;

use Illuminate\Contracts\Database\Eloquent\Builder;
use Modules\EmployeeSalaryItems\Entities\EmployeeSalaryItem;

class PayslipService
{
    function getCategoryOneData($employee_id) {
        $data = EmployeeSalaryItem::query()
            ->where('company_id', auth()->user()->company_id)
            ->where('employee_id', $employee_id)
            ->whereHas(
                'salaryItemsName', function(Builder $query){
                    $query->where('salary_items_category_id', 1);
                }
            )
            ->get();
        return $data;
    }
}