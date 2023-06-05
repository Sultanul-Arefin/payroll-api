<?php

namespace Modules\EmployeeSalaryItems\Http\Controllers;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Modules\EmployeeSalaryItems\Entities\EmployeeSalaryItem;
use Modules\EmployeeSalaryItems\Http\Requests\StoreEmployeeSalaryItems;

class EmployeeSalaryItemsController extends Controller
{
    public function store(StoreEmployeeSalaryItems $request)
    {
        $employee_salary = DB::transaction(function() use($request){
            $employee_salary = EmployeeSalaryItem::create([
                'salary_item_id' => $request->salary_item_id,
                'company_id' => auth()->user()->company_id,
                'is_percentage' => $request->is_percentage,
                'is_general' => $request->is_general,
                'employee_id' => $request->employee_id ?? null,
                'amount' => $request->amount ?? null
            ]);
            return $employee_salary;
        });
        return apiResponse(
            data: $employee_salary,
            message: 'Salary Items Associate Successfully',
            status: 'success'
        );
    }
}
