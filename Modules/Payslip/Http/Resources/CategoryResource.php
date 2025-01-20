<?php

namespace Modules\Payslip\Http\Resources;

use Carbon\Carbon;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Arr;
use JsonSerializable;
use Modules\Attendance\Entities\Attendance;
use Modules\EmployeeSalaryItems\Entities\DeductionDetails;
use Modules\EmployeeSalaryItems\Entities\EmployeeSalaryItem;
use Modules\LeaveManagement\Entities\UserLeave;

class CategoryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  Request  $request
     * @return array|Arrayable|JsonSerializable
     */
    public function toArray($request)
    {
        return [
            'category_id' => $this->id,
            'category_name' => $this->name,
            'amount' => $this->getAmount($this->id),
        ];
    }

    public function getAmount($category_id)
    {
        if ($category_id == 7) {

            $employee_deduction = DeductionDetails::query()
                ->whereHas(
                    'employee_salary_item', function (Builder $builder) use ($category_id) {
                        $builder
                            ->where('company_id', auth()->user()->company_id)
                            ->where('employee_id', request('employee_id'))
                            ->whereHas(
                                'salaryItemsName', function (Builder $builder) use ($category_id) {
                                    $builder->whereHas(
                                        'salaryItemsCategory', function (Builder $builder) use ($category_id) {
                                            $builder->where('id', $category_id);
                                        }
                                    );
                                }
                            );
                    }
                )
                ->get();
                // ->sum('employee_amount');

            $employee_deduction_value = 0;
            foreach($employee_deduction as $value)
            {
                $gross_pay_before_tax = ($this->getAmountForEmployee(1, request('employee_id')) - $this->getAmountForEmployee(2, request('employee_id'))) + $this->getAmountForEmployee(3, request('employee_id'));
                if($value->employee_salary_item->is_percentage == 1){
                    $employee_deduction_value += round($gross_pay_before_tax * ($value->employee_amount / 100), 2);
                } else{
                    $employee_deduction_value += round($value->employee_amount, 2);
                }
            }

            // GET CATEGORY ID 8 DEDUCTION VALUE
            $other_complimentary_deduction = DeductionDetails::query()
                ->whereHas(
                    'employee_salary_item', function (Builder $builder) use ($category_id) {
                        $builder
                            ->where('company_id', auth()->user()->company_id)
                            ->where('employee_id', request('employee_id'))
                            ->whereHas(
                                'salaryItemsName', function (Builder $builder) use ($category_id) {
                                    $builder->whereHas(
                                        'salaryItemsCategory', function (Builder $builder) use ($category_id) {
                                            $builder->where('id', 8);
                                        }
                                    );
                                }
                            );
                    }
                )
                ->get();
                // ->sum('employee_amount');

            $other_deduction_value = 0;
            foreach($other_complimentary_deduction as $value)
            {
                $gross_pay_before_tax = ($this->getAmountForEmployee(1, request('employee_id')) - $this->getAmountForEmployee(2, request('employee_id'))) + $this->getAmountForEmployee(3, request('employee_id'));
                if($value->employee_salary_item->is_percentage == 1){
                    $other_deduction_value += round($gross_pay_before_tax * ($value->employee_amount / 100), 2);
                } else{
                    $other_deduction_value += round($value->employee_amount, 2);
                }
            }

            return [
                'employee_government_deduction' => $employee_deduction_value,
                'other_complimentary_deduction' => $other_deduction_value,
                'employee_deduction' => $employee_deduction_value + $other_deduction_value,
            ];
        } elseif ($category_id == 8) {
            $company_government_contribution = DeductionDetails::query()
                ->whereHas(
                    'employee_salary_item', function (Builder $builder) use ($category_id) {
                        $builder
                            ->where('company_id', auth()->user()->company_id)
                            ->where('employee_id', request('employee_id'))
                            ->whereHas(
                                'salaryItemsName', function (Builder $builder) use ($category_id) {
                                    $builder->whereHas(
                                        'salaryItemsCategory', function (Builder $builder) use ($category_id) {
                                            $builder->where('id', $category_id);
                                        }
                                    );
                                }
                            );
                    }
                )
                ->get();
                // ->sum('government_or_company_amount');

            $company_government_contribution_value = 0;
            foreach($company_government_contribution as $value)
            {
                $gross_pay_before_tax = ($this->getAmountForEmployee(1, request('employee_id')) - $this->getAmountForEmployee(2, request('employee_id'))) + $this->getAmountForEmployee(3, request('employee_id'));
                if($value->employee_salary_item->is_percentage == 1){
                    $company_government_contribution_value += round($gross_pay_before_tax * ($value->government_or_company_amount / 100), 2);
                } else{
                    $company_government_contribution_value += round($value->government_or_company_amount, 2);
                }
            }

            // GET CATEGORY ID 7 DEDUCTION VALUE
            $company_other_complimentary_contribution = DeductionDetails::query()
                ->whereHas(
                    'employee_salary_item', function (Builder $builder) use ($category_id) {
                        $builder
                            ->where('company_id', auth()->user()->company_id)
                            ->where('employee_id', request('employee_id'))
                            ->whereHas(
                                'salaryItemsName', function (Builder $builder) use ($category_id) {
                                    $builder->whereHas(
                                        'salaryItemsCategory', function (Builder $builder) use ($category_id) {
                                            $builder->where('id', 7);
                                        }
                                    );
                                }
                            );
                    }
                )
                ->get();
                // ->sum('government_or_company_amount');

            $company_other_complimentary_contribution_value = 0;
            foreach($company_other_complimentary_contribution as $value)
            {
                $gross_pay_before_tax = ($this->getAmountForEmployee(1, request('employee_id')) - $this->getAmountForEmployee(2, request('employee_id'))) + $this->getAmountForEmployee(3, request('employee_id'));
                if($value->employee_salary_item->is_percentage == 1){
                    $company_other_complimentary_contribution_value += round($gross_pay_before_tax * ($value->government_or_company_amount / 100), 2);
                } else{
                    $company_other_complimentary_contribution_value += round($value->government_or_company_amount, 2);
                }
            }
            return [
                'company_government_contribution' => $company_government_contribution_value,
                'company_other_complimentary_contribution' => $company_other_complimentary_contribution_value,
                'company_contribution' => $company_government_contribution_value + $company_other_complimentary_contribution_value
            ];

            return EmployeeSalaryItem::query()
                ->whereHas(
                    'salaryItemsName', function (Builder $builder) use ($category_id) {
                        $builder->whereHas(
                            'salaryItemsCategory', function (Builder $builder) use ($category_id) {
                                $builder->where('id', $category_id);
                            }
                        );
                    }
                )
                ->where('company_id', auth()->user()->company_id)
                ->where('employee_id', request('employee_id'))
                ->get()->sum('amount');
        } elseif ($category_id == 1) {
            $amount = $this->getCategoryIdOneAmount(1);
            return $amount;
        } elseif($category_id == 2){
            $unpaid_absent_count = UserLeave::query()
                                    ->whereHas(
                                        'salary_item', function(Builder $builder){
                                            $builder->where('company_id', auth()->user()->company_id)
                                                    ->where(function($query){
                                                        $query->where('name', 'Absent')
                                                            ->orWhere('name', 'Unpaid Sick Leave');
                                                    });
                                        }
                                    )
                                    ->where('user_id', request('employee_id'))
                                    ->get();
            $count = 0;
            foreach($unpaid_absent_count as $value)
            {
                $count = $value?->leave_details?->count();
            }

            // get absent, unpaid leave value
            $absent_unpaid_value = EmployeeSalaryItem::query()
                ->whereHas(
                    'salaryItemsName', function (Builder $builder) use ($category_id) {
                        $builder->where(function($query){
                                $query->where('name', 'Absent')
                                    ->orWhere('name', 'Unpaid Sick Leave');
                            })
                        ->whereHas(
                            'salaryItemsCategory', function (Builder $builder) use ($category_id) {
                                $builder->where('id', 2);
                            }
                        );
                    }
                )
                ->where('company_id', auth()->user()->company_id)
                ->where('employee_id', request('employee_id'))
                ->get();
            return $count * ($absent_unpaid_value->count() > 0 ? $absent_unpaid_value[0]->amount : 0);
        } elseif($category_id == 5){
            $categoryOneAmount = $this->getCategoryIdOneAmount(1);

            $straight_without_percentage =  EmployeeSalaryItem::query()
                ->whereHas(
                    'salaryItemsName', function (Builder $builder) use ($category_id) {
                        $builder->where('is_threshold', 1)
                                ->whereHas(
                                    'salaryItemsCategory', function (Builder $builder) use ($category_id) {
                                        $builder->where('id', $category_id);
                                    }
                        );
                    }
                )
                ->where('company_id', auth()->user()->company_id)
                ->where('is_percentage', 0)
                ->where(function ($query) {
                    $query->where('employee_id', request('employee_id'))
                          ->orWhere(function ($query) {
                              $query->whereNull('employee_id')
                                    ->where('is_general', 1);
                          });
                })
                ->get()->sum('amount');
            $straight_percentage =  EmployeeSalaryItem::query()
                ->whereHas(
                    'salaryItemsName', function (Builder $builder) use ($category_id) {
                        $builder->where('is_threshold', 1)
                                ->whereHas(
                                    'salaryItemsCategory', function (Builder $builder) use ($category_id) {
                                        $builder->where('id', $category_id);
                                    }
                        );
                    }
                )
                ->where('company_id', auth()->user()->company_id)
                ->where('is_percentage', 1)
                ->where(function ($query) {
                    $query->where('employee_id', request('employee_id'))
                          ->orWhere(function ($query) {
                              $query->whereNull('employee_id')
                                    ->where('is_general', 1);
                          });
                })
                ->get();
            $straight_percentage_value = 0;
            foreach($straight_percentage as $value)
            {
                $straight_percentage_value += ($categoryOneAmount * ($value->amount / 100));
            }

            $straight = $straight_without_percentage + $straight_percentage_value;

            $threshold =  EmployeeSalaryItem::query()
                ->whereHas(
                    'salaryItemsName', function (Builder $builder) use ($category_id) {
                        $builder->where('is_threshold', 2)
                                ->whereHas(
                                    'salaryItemsCategory', function (Builder $builder) use ($category_id) {
                                        $builder->where('id', $category_id);
                                    }
                        );
                    }
                )
                ->where('company_id', auth()->user()->company_id)
                ->where('is_percentage', 1)
                ->where(function ($query) {
                    $query->where('employee_id', request('employee_id'))
                          ->orWhere(function ($query) {
                              $query->whereNull('employee_id')
                                    ->where('is_general', 1);
                          });
                })
                ->get();

            $threshold_value = 0;
            foreach($threshold as $value){
                $percentage_amount = $value->amount; // get the percentage value
                $threshold_details = $value->threshold_details; // get threshold details to check if the wages is between the details
                if($threshold_details)
                {
                    $start_percentage_after = $threshold_details->start_percentage_after;
                    $end_percentage_at = $threshold_details->end_percentage_at;

                    if ($start_percentage_after <= $categoryOneAmount && $end_percentage_at >= $categoryOneAmount) {
                        $threshold_value += ($categoryOneAmount * ($percentage_amount / 100));
                    }
                }
            }
            return round($straight + $threshold_value, 2);
        } else {
            return EmployeeSalaryItem::query()
                ->whereHas(
                    'salaryItemsName', function (Builder $builder) use ($category_id) {
                        $builder->whereHas(
                            'salaryItemsCategory', function (Builder $builder) use ($category_id) {
                                $builder->where('id', $category_id);
                            }
                        );
                    }
                )
                ->where('company_id', auth()->user()->company_id)
                ->where(function ($query) {
                    $query->where('employee_id', request('employee_id'))
                          ->orWhere(function ($query) {
                              $query->whereNull('employee_id')
                                    ->where('is_general', 1);
                          });
                })
                ->get()->sum('amount');
        }
    }

    public function getCategoryIdOneAmount($category_id){
        $amount = EmployeeSalaryItem::query()
            ->whereHas(
                'salaryItemsName', function (Builder $builder) {
                    $builder->where('name', 'Wages')
                        ->whereHas(
                            'salaryItemsCategory', function (Builder $builder) {
                                $builder->where('id', 1);
                            }
                        );
                }
            )
            ->where('company_id', auth()->user()->company_id)
            ->where('employee_id', request('employee_id'))
            ->get()->sum('amount');
        if($amount <= 0){
            $hourly_amount = EmployeeSalaryItem::query()
                ->where('employee_id', request('employee_id'))
                ->whereHas(
                    'salaryItemsName', function (Builder $builder) {
                        $builder
                            ->where('name', 'Ordinary Time Rate')
                            ->where('salary_items_category_id', 1);
                    }
                )
                ->first('amount');
            $hours_worked = (int)request('working_hours');
            // $hours_worked = $this->get_hours_worked(request('employee_id'), request('from_date'), request('to_date'));
            if($hours_worked < 0){
                $employee_associated_amount = 0;
            } else{
                $employee_associated_amount = $hourly_amount->amount * $hours_worked;
            }
            return $employee_associated_amount;
        }
        return $amount;
    }

    // hours worked for employee
    public function get_hours_worked($employee_id, $from_date, $to_date)
    {
        $attendances = Attendance::query()
                        ->where('user_id', $employee_id)
                        ->where('status', Attendance::PRESENT)
                        ->whereBetween(
                            'dates',
                            [
                                $from_date,
                                $to_date
                            ]
                        )
                        ->get();
        $total_minutes = 0;
        foreach($attendances as $value)
        {
            $details = $value->attendance_details;

            foreach($details as $detail)
            {
                $inTime = Carbon::parse($detail->in_time);
                $outTime = Carbon::parse($detail->out_time);

                // Calculate the time difference in minutes and add it to the total
                $timeDifferenceMinutes = $inTime->diffInHours($outTime); // have to check this code twice, there might be an issue in the inTime, outTime alignment
                // previous alignment
                // $timeDifferenceMinutes = $outTime->diffInHours($inTime);
                $total_minutes += $timeDifferenceMinutes;
            }
        }
        return $total_minutes;
    }

    // employee-salary-items-calculation
    public function getAmountForEmployee($category_id, $employee_id)
    {
        if ($category_id == 7) {
            $employee_deduction = DeductionDetails::query()
                ->whereHas(
                    'employee_salary_item', function (Builder $builder) use ($category_id, $employee_id) {
                        $builder
                            ->where('company_id', auth()->user()->company_id)
                            ->where('employee_id', $employee_id)
                            ->whereHas(
                                'salaryItemsName', function (Builder $builder) use ($category_id) {
                                    $builder->whereHas(
                                        'salaryItemsCategory', function (Builder $builder) use ($category_id) {
                                            $builder->where('id', $category_id);
                                        }
                                    );
                                }
                            );
                    }
                )
                ->get();
                // ->sum('employee_amount');

            $employee_deduction_value = 0;
            foreach($employee_deduction as $value)
            {
                $gross_pay_before_tax = ($this->getAmountForEmployee(1, $employee_id) - $this->getAmountForEmployee(2, $employee_id)) + $this->getAmountForEmployee(3, $employee_id);
                if($value->employee_salary_item->is_percentage == 1){
                    $employee_deduction_value += round($gross_pay_before_tax * ($value->employee_amount / 100), 2);
                } else{
                    $employee_deduction_value += round($value->employee_amount, 2);
                }
            }

            // GET CATEGORY ID 8 DEDUCTION VALUE
            $other_complimentary_deduction = DeductionDetails::query()
                ->whereHas(
                    'employee_salary_item', function (Builder $builder) use ($category_id, $employee_id) {
                        $builder
                            ->where('company_id', auth()->user()->company_id)
                            ->where('employee_id', $employee_id)
                            ->whereHas(
                                'salaryItemsName', function (Builder $builder) use ($category_id) {
                                    $builder->whereHas(
                                        'salaryItemsCategory', function (Builder $builder) use ($category_id) {
                                            $builder->where('id', 8);
                                        }
                                    );
                                }
                            );
                    }
                )
                ->get();
                // ->sum('employee_amount');

                $other_deduction_value = 0;
                foreach($other_complimentary_deduction as $value)
                {
                    $gross_pay_before_tax = ($this->getAmountForEmployee(1, $employee_id) - $this->getAmountForEmployee(2, $employee_id)) + $this->getAmountForEmployee(3, $employee_id);
                    if($value->employee_salary_item->is_percentage == 1){
                        $other_deduction_value += round($gross_pay_before_tax * ($value->employee_amount / 100), 2);
                    } else{
                        $other_deduction_value += round($value->employee_amount, 2);
                    }
                }
            return $employee_deduction_value + $other_deduction_value;
        } elseif ($category_id == 8) {
            return [
                'company_government_contribution' => 0.00,
                'company_other_complimentary_contribution' => 0.00,
                'company_contribution' => 0.00,
            ];

            return EmployeeSalaryItem::query()
                ->whereHas(
                    'salaryItemsName', function (Builder $builder) use ($category_id) {
                        $builder->whereHas(
                            'salaryItemsCategory', function (Builder $builder) use ($category_id) {
                                $builder->where('id', $category_id);
                            }
                        );
                    }
                )
                ->where('company_id', auth()->user()->company_id)
                ->where('employee_id', request('employee_id'))
                ->get()->sum('amount');
        } elseif ($category_id == 1) {
            $overtime = 0;
            if(request('overtime')){
                $overtime = (int)request('overtime') * $this->getSalaryItemAmount("Overtime Rate")->amount;
            }
            $double_overtime = 0;
            if(request('double_overtime')){
                $double_overtime = (int)request('double_overtime') * $this->getSalaryItemAmount("Double Overtime Rate")->amount;
            }
            $bonus = 0;
            if(request('bonus')){
                $bonus = (int)request('bonus') * $this->getSalaryItemAmount("Bonus")->amount;
            }
            $amount = EmployeeSalaryItem::query()
                ->whereHas(
                    'salaryItemsName', function (Builder $builder) {
                        $builder->where('name', 'Wages')
                            ->whereHas(
                                'salaryItemsCategory', function (Builder $builder) {
                                    $builder->where('id', 1);
                                }
                            );
                    }
                )
                ->where('company_id', auth()->user()->company_id)
                ->where('employee_id', request('employee_id'))
                ->get()->sum('amount');
            if($amount <= 0){
                $hourly_amount = EmployeeSalaryItem::query()
                    ->where('employee_id', request('employee_id'))
                    ->whereHas(
                        'salaryItemsName', function (Builder $builder) {
                            $builder
                                ->where('name', 'Ordinary Time Rate')
                                ->where('salary_items_category_id', 1);
                        }
                    )
                    ->first('amount');
                $hours_worked = (int)request('working_hours');
                // $hours_worked = $this->get_hours_worked(request('employee_id'), request('from_date'), request('to_date'));
                if($hours_worked < 0){
                    $employee_associated_amount = 0;
                } else{
                    $employee_associated_amount = $hourly_amount->amount * $hours_worked;
                }
                return $employee_associated_amount + $overtime + $double_overtime + $bonus;
            }
            return $amount + $overtime + $double_overtime + $bonus;
        } elseif($category_id == 2){
            $unpaid_absent_count = UserLeave::query()
                                    ->whereHas(
                                        'salary_item', function(Builder $builder){
                                            $builder->where('company_id', auth()->user()->company_id)
                                                    ->where(function($query){
                                                        $query->where('name', 'Absent')
                                                            ->orWhere('name', 'Unpaid Sick Leave');
                                                    });
                                        }
                                    )
                                    ->where('user_id', request('employee_id'))
                                    ->get();
            $count = 0;
            foreach($unpaid_absent_count as $value)
            {
                $count = $value?->leave_details?->count();
            }

            // get absent, unpaid leave value
            $absent_unpaid_value = EmployeeSalaryItem::query()
                ->whereHas(
                    'salaryItemsName', function (Builder $builder) use ($category_id) {
                        $builder->where(function($query){
                                $query->where('name', 'Absent')
                                    ->orWhere('name', 'Unpaid Sick Leave');
                            })
                        ->whereHas(
                            'salaryItemsCategory', function (Builder $builder) use ($category_id) {
                                $builder->where('id', 2);
                            }
                        );
                    }
                )
                ->where('company_id', auth()->user()->company_id)
                ->where('employee_id', request('employee_id'))
                ->get();
            return $count * ($absent_unpaid_value->count() > 0 ? $absent_unpaid_value[0]->amount : 0);
        } elseif($category_id == 5){
            $straight =  EmployeeSalaryItem::query()
                ->whereHas(
                    'salaryItemsName', function (Builder $builder) use ($category_id) {
                        $builder->where('is_threshold', 1)
                                ->whereHas(
                                    'salaryItemsCategory', function (Builder $builder) use ($category_id) {
                                        $builder->where('id', $category_id);
                                    }
                        );
                    }
                )
                ->where('company_id', auth()->user()->company_id)
                ->where('is_percentage', 0)
                ->where(function ($query) {
                    $query->where('employee_id', request('employee_id'))
                          ->orWhere(function ($query) {
                              $query->whereNull('employee_id')
                                    ->where('is_general', 1);
                          });
                })
                ->get()->sum('amount');
            $threshold =  EmployeeSalaryItem::query()
                ->whereHas(
                    'salaryItemsName', function (Builder $builder) use ($category_id) {
                        $builder->where('is_threshold', 2)
                                ->whereHas(
                                    'salaryItemsCategory', function (Builder $builder) use ($category_id) {
                                        $builder->where('id', $category_id);
                                    }
                        );
                    }
                )
                ->where('company_id', auth()->user()->company_id)
                ->where('is_percentage', 1)
                ->where(function ($query) {
                    $query->where('employee_id', request('employee_id'))
                          ->orWhere(function ($query) {
                              $query->whereNull('employee_id')
                                    ->where('is_general', 1);
                          });
                })
                ->get();
            $company = User::where('id', request('employee_id'))->first();
            $categoryOneAmount = $this->getCategoryIdOneAmount(1, $company->id, $employee_id);
            $threshold_value = 0;
            foreach($threshold as $value){
                $percentage_amount = $value->amount; // get the percentage value
                $threshold_details = $value->threshold_details; // get threshold details to check if the wages is between the details
                if($threshold_details)
                {
                    $start_percentage_after = $threshold_details->start_percentage_after;
                    $end_percentage_at = $threshold_details->end_percentage_at;

                    if ($start_percentage_after <= $categoryOneAmount && $end_percentage_at >= $categoryOneAmount) {
                        $threshold_value += ($categoryOneAmount * ($percentage_amount / 100));
                    }
                }
            }
            return round($straight + $threshold_value, 2);
        } else {
            return EmployeeSalaryItem::query()
                ->whereHas(
                    'salaryItemsName', function (Builder $builder) use ($category_id) {
                        $builder->whereHas(
                            'salaryItemsCategory', function (Builder $builder) use ($category_id) {
                                $builder->where('id', $category_id);
                            }
                        );
                    }
                )
                ->where('company_id', auth()->user()->company_id)
                ->where(function ($query) use($employee_id){
                    $query->where('employee_id', $employee_id)
                          ->orWhere(function ($query) {
                              $query->whereNull('employee_id')
                                    ->where('is_general', 1);
                          });
                })
                ->get()->sum('amount');
        }
    }

    public function getSalaryItemAmount($name)
    {
        return EmployeeSalaryItem::query()
                    ->where('employee_id', request('employee_id'))
                    ->whereHas(
                        'salaryItemsName', function (Builder $builder) use($name) {
                            $builder
                                ->where('name', $name)
                                ->where('salary_items_category_id', 1);
                        }
                    )
                    ->first('amount');
    }
}
