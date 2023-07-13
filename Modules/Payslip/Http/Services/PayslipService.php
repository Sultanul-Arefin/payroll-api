<?php

namespace Modules\Payslip\Http\Services;

use Modules\EmployeeSalaryItems\Entities\EmployeeSalaryItem;

class PayslipService
{
    function getCategoryOneData($employee_id) {
        $data = EmployeeSalaryItem::query()
            // ->whereHas('salaryItemsName')
            // ->where('company_id', auth()->user()->company_id)
            // ->where('employee_id', $employee_id)
            ->get();
    }
}