<?php

namespace Modules\Payslip\Http\Services;

use Exception;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Modules\EmployeeSalaryItems\Entities\EmployeeSalaryItem;
use Modules\Payslip\Entities\PayslipDetail;

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

    function get_total_amount($employee_id) {
        $employee_associated_amount = EmployeeSalaryItem::query()
                                    ->where('employee_id', $employee_id)
                                    ->whereHas(
                                        'salaryItemsName', function(Builder $builder){
                                            $builder->whereNotIn('salary_items_category_id', [1,2]);
                                        }
                                    )
                                    ->sum('amount');
        return $employee_associated_amount;
    }

    function add_payslip_details($payslip_id, $employee_id) {
        $employee_associated_amount = EmployeeSalaryItem::query()
                                    ->where('employee_id', $employee_id)
                                    ->get();
        foreach($employee_associated_amount as $value){
            PayslipDetail::create([
                'payslip_id' => $payslip_id,
                'salary_item_id' => $value->id,
                'amount' => $value->amount
            ]);
        }
    }
}