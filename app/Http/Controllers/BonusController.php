<?php

namespace App\Http\Controllers;

use App\Http\Requests\BonusRequest;
use App\Models\Bonus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Modules\EmployeeSalaryItems\Entities\EmployeeSalaryItem;

class BonusController extends Controller
{
    public function store(BonusRequest $request)
    {
        $bonus = Bonus::create([
            'employee_id' => $request->employee_id,
            'type' => $request->type,
            'given_amount' => $request->given_amount,
            'generalize_amount' => $this->calculate_generalize_amount($request->type, $request->given_amount, $request->employee_id),
            'date' => $request->date
        ]);

        return $this->apiResponse(
            data: $bonus,
            message: 'Bonus Added Successfully'
        );
    }

    public function calculate_generalize_amount($type, $amount, $employee_id)
    {
        if($type == 0 || $type == 3 || $type == 4 || $type == 5){
            return $amount;
        }
        if($type == 1){
            $basic = EmployeeSalaryItem::query()
                    ->where('employee_id', $employee_id)
                    ->whereHas(
                        'salaryItemsName', function (Builder $builder) {
                            $builder
                                ->where('name', 'Ordinary Time Rate')
                                ->where('salary_items_category_id', 1);
                        }
                    )
                    ->first('amount');
            return ($this->get_basic($employee_id) * ($amount / 100)) / $basic->amount;
        }
        if($type == 2){
           return $amount / $this->get_ordinary_time_rate($employee_id);
        }
        // return match($type){
        //     "0" => $amount,
        //     "3" => $amount,
        //     "4" => $amount,
        //     "5" => $amount,
        //     "1" => $this->get_basic($employee_id) % $amount,
        //     "2" => $amount / $this->get_ordinary_time_rate($employee_id)
        // };
    }

    public function get_ordinary_time_rate($employee_id)
    {
        $amount = EmployeeSalaryItem::query()
                    ->where('employee_id', $employee_id)
                    ->whereHas(
                        'salaryItemsName', function (Builder $builder) {
                            $builder
                                ->where('name', 'Ordinary Time Rate')
                                ->where('salary_items_category_id', 1);
                        }
                    )
                    ->first('amount');
        return $amount->amount;
    }

    public function get_basic($employee_id){
        $amount = EmployeeSalaryItem::query()
                    ->where('employee_id', $employee_id)
                    ->whereHas(
                        'salaryItemsName', function (Builder $builder) {
                            $builder
                                ->where('name', 'Wages')
                                ->where('salary_items_category_id', 1);
                        }
                    )
                    ->first('amount');
        return $amount->amount;
    }
}
