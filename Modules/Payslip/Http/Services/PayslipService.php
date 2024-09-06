<?php

namespace Modules\Payslip\Http\Services;

use Carbon\Carbon;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Modules\Attendance\Entities\Attendance;
use Modules\EmployeeSalaryItems\Entities\DeductionDetails;
use Modules\EmployeeSalaryItems\Entities\EmployeeSalaryItem;
use Modules\LeaveManagement\Entities\UserLeave;
use Modules\Payslip\Entities\PayslipDetail;
use Modules\Payslip\Entities\PayslipDetailsForDeduction;

class PayslipService
{
    public function getCategoryOneData($employee_id)
    {
        $data = EmployeeSalaryItem::query()
            ->where('company_id', auth()->user()->company_id)
            ->where('employee_id', $employee_id)
            ->whereHas(
                'salaryItemsName', function (Builder $query) {
                    $query->where('salary_items_category_id', 1);
                }
            )
            ->get();

        return $data;
    }

    // function get_total_amount_except_basic_attendance($employee_id) {
    //     $employee_associated_amount = EmployeeSalaryItem::query()
    //                                 ->where('employee_id', $employee_id)
    //                                 ->whereHas(
    //                                     'salaryItemsName', function(Builder $builder){
    //                                         $builder->whereNotIn('salary_items_category_id', [1,2]);
    //                                     }
    //                                 )
    //                                 ->sum('amount');
    //     return $employee_associated_amount;
    // }

    public function add_payslip_details($payslip_id, $employee_id, $company_id)
    {
        $employee_associated_amount = EmployeeSalaryItem::query()
                                    // ->whereHas(
                                    //     'salaryItemsName', function(Builder $builder){
                                    //         $builder->whereNotIn('salary_items_category_id', [1,2]);
                                    //     }
                                    // )
            ->where('employee_id', $employee_id)
            ->whereNotNull('amount')
            ->get();
        foreach ($employee_associated_amount as $value) {
            if($this->getAmountCalculation($value) != 0){
                PayslipDetail::create([
                    'payslip_id' => $payslip_id,
                    'salary_item_id' => $value->salary_item_id,
                    'amount' => $this->getAmountCalculation($value),
                    // 'amount' => $value->amount,
                ]);
            }
        }

        // FOR DEDUCTION
        $salary_deduction_values = EmployeeSalaryItem::query()
                            ->whereHas(
                                'salaryItemsName', function(Builder $builder){
                                    $builder->whereIn('salary_items_category_id', [7,8]);
                                }
                            )
                            ->where('employee_id', $employee_id)
                            ->whereNull('amount')
                            ->get();
        foreach($salary_deduction_values as $value){
            if($value->salaryItemsName->salaryItemsCategory->id == 7){
                $deduction_value = DeductionDetails::query()
                    ->whereHas(
                        'employee_salary_item', function (Builder $builder) use ($employee_id, $company_id) {
                            $builder
                                ->where('company_id', $company_id)
                                ->where('employee_id', $employee_id)
                                ->whereHas(
                                    'salaryItemsName', function (Builder $builder){
                                        $builder->whereHas(
                                            'salaryItemsCategory', function (Builder $builder){
                                                $builder->where('id', 7);
                                            }
                                        );
                                    }
                                );
                        }
                    )
                    ->get();
                    // ->sum('government_or_company_amount');
                foreach($deduction_value as $dv){
                    PayslipDetailsForDeduction::create([
                        'payslip_id' => $payslip_id,
                        'salary_item_id' => $dv->employee_salary_item?->salary_item_id,
                        'employee_amount' => $dv->employee_amount,
                        'government_or_company_amount' => $dv->government_or_company_amount
                    ]);
                }
            }
            if($value->salaryItemsName->salaryItemsCategory->id == 8){
                $deduction_value = DeductionDetails::query()
                    ->whereHas(
                        'employee_salary_item', function (Builder $builder) use ($employee_id, $company_id) {
                            $builder
                                ->where('company_id', $company_id)
                                ->where('employee_id', $employee_id)
                                ->whereHas(
                                    'salaryItemsName', function (Builder $builder){
                                        $builder->whereHas(
                                            'salaryItemsCategory', function (Builder $builder){
                                                $builder->where('id', 8);
                                            }
                                        );
                                    }
                                );
                        }
                    )
                    ->get();
                    // ->sum('government_or_company_amount');
                foreach($deduction_value as $dv){
                    PayslipDetailsForDeduction::create([
                        'payslip_id' => $payslip_id,
                        'salary_item_id' => $dv->employee_salary_item?->salary_item_id,
                        'employee_amount' => $dv->employee_amount,
                        'government_or_company_amount' => $dv->government_or_company_amount
                    ]);
                }
            }
        }
    }

    function getAmountCalculation($salary_item)
    {
        if($salary_item->salaryItemsName->name == "Wages"){
            return $salary_item->amount;
        }
        if($salary_item->salaryItemsName->salaryItemsCategory->id == 1){
            return 0;
        }
        if($salary_item->salaryItemsName->salaryItemsCategory->id == 2){
            return $salary_item->amount * $this->get_leave_details(request('employee_id'), $salary_item->salaryItemsName->id); // * no of leave days/hours
        }
        return $salary_item->amount;
    }

    public function get_leave_details($employee_id, $item_id): int
    {
        $leave = UserLeave::query()
            ->where('user_id', $employee_id)
            ->where('status', UserLeave::APPROVED)
            ->where('leave_type', $item_id)
            ->get();
        $count = 0;
        foreach($leave as $value)
        {
            $count = $value?->leave_details?->count();
        }
        return $count;
    }

    public function get_basic_amount($employee_id)
    {
        $employee_associated_amount = EmployeeSalaryItem::query()
            ->where('employee_id', $employee_id)
            ->whereHas(
                'salaryItemsName', function (Builder $builder) {
                    $builder
                        ->where('name', 'Wages')
                        ->where('salary_items_category_id', 1);
                }
            )
            ->sum('amount');

        return $employee_associated_amount;
    }

    public function get_staff_deduction_sick_absent_amount($employee_id, $company_id)
    {
        $unpaid_absent_count = UserLeave::query()
                                    ->whereHas(
                                        'salary_item', function(Builder $builder)use($company_id){
                                            $builder->where('company_id', $company_id)
                                                    ->where(function($query){
                                                        $query->where('name', 'Absent')
                                                            ->orWhere('name', 'Unpaid Sick Leave');
                                                    });
                                        }
                                    )
                                    ->where('user_id', $employee_id)
                                    ->get();
            $count = 0;
            foreach($unpaid_absent_count as $value)
            {
                $count = $value?->leave_details?->count();
            }

            // get absent, unpaid leave value
            $absent_unpaid_value = EmployeeSalaryItem::query()
                ->whereHas(
                    'salaryItemsName', function (Builder $builder) {
                        $builder->where(function($query){
                                $query->where('name', 'Absent')
                                    ->orWhere('name', 'Unpaid Sick Leave');
                            })
                        ->whereHas(
                            'salaryItemsCategory', function (Builder $builder) {
                                $builder->where('id', 2);
                            }
                        );
                    }
                )
                ->where('company_id', $company_id)
                ->where('employee_id', $employee_id)
                ->get();
            return $count * ($absent_unpaid_value->count() > 0 ? $absent_unpaid_value[0]->amount : 0);
    }

    public function get_taxable_allowance_amount($employee_id)
    {
        $employee_associated_amount = EmployeeSalaryItem::query()
            ->where('employee_id', $employee_id)
            ->whereHas(
                'salaryItemsName', function (Builder $builder) {
                    $builder->where('salary_items_category_id', 3);
                }
            )
            ->sum('amount');

        return $employee_associated_amount;
    }

    public function get_non_taxable_allowance_amount($employee_id)
    {
        $employee_associated_amount = EmployeeSalaryItem::query()
            ->where('employee_id', $employee_id)
            ->whereHas(
                'salaryItemsName', function (Builder $builder) {
                    $builder->where('salary_items_category_id', 4);
                }
            )
            ->sum('amount');

        return $employee_associated_amount;
    }

    public function get_income_taxes_amount($employee_id)
    {
        $employee_associated_amount = EmployeeSalaryItem::query()
            ->where('employee_id', $employee_id)
            ->whereHas(
                'salaryItemsName', function (Builder $builder) {
                    $builder->where('salary_items_category_id', 5);
                }
            )
            ->sum('amount');

        return $employee_associated_amount;
    }

    public function get_additional_taxes_tax_top_up_amount($employee_id)
    {
        $employee_associated_amount = EmployeeSalaryItem::query()
            ->where('employee_id', $employee_id)
            ->whereHas(
                'salaryItemsName', function (Builder $builder) {
                    $builder->where('salary_items_category_id', 6);
                }
            )
            ->sum('amount');
        return $employee_associated_amount;
    }

    public function government_deduction_amount($employee_id, $company_id)
    {
        // GET VALUE FROM CATEGORY 7
        $employee_contribution_value = DeductionDetails::query()
            ->whereHas(
                'employee_salary_item', function (Builder $builder) use ($employee_id, $company_id) {
                    $builder
                        ->where('company_id', $company_id)
                        ->where('employee_id', $employee_id)
                        ->whereHas(
                            'salaryItemsName', function (Builder $builder){
                                $builder->whereHas(
                                    'salaryItemsCategory', function (Builder $builder){
                                        $builder->where('id', 7);
                                    }
                                );
                            }
                        );
                }
            )
            ->get()
            ->sum('employee_amount');

        // GET VALUE FROM CATEGORY 8
        $other_company_deduction = DeductionDetails::query()
            ->whereHas(
                'employee_salary_item', function (Builder $builder) use ($employee_id, $company_id) {
                    $builder
                        ->where('company_id', $company_id)
                        ->where('employee_id', $employee_id)
                        ->whereHas(
                            'salaryItemsName', function (Builder $builder){
                                $builder->whereHas(
                                    'salaryItemsCategory', function (Builder $builder){
                                        $builder->where('id', 8);
                                    }
                                );
                            }
                        );
                }
            )
            ->get()
            ->sum('employee_amount');

        return $employee_contribution_value + $other_company_deduction;
    }

    public function other_complimentary_deduction_amount($employee_id, $company_id)
    {
        // GET VALUE FROM CATEGORY 7
        $company_contribution_value = DeductionDetails::query()
            ->whereHas(
                'employee_salary_item', function (Builder $builder) use ($employee_id, $company_id) {
                    $builder
                        ->where('company_id', $company_id)
                        ->where('employee_id', $employee_id)
                        ->whereHas(
                            'salaryItemsName', function (Builder $builder){
                                $builder->whereHas(
                                    'salaryItemsCategory', function (Builder $builder){
                                        $builder->where('id', 7);
                                    }
                                );
                            }
                        );
                }
            )
            ->get()
            ->sum('government_or_company_amount');

        // GET VALUE FROM CATEGORY 8
        $other_company_contribution = DeductionDetails::query()
            ->whereHas(
                'employee_salary_item', function (Builder $builder) use ($employee_id, $company_id) {
                    $builder
                        ->where('company_id', $company_id)
                        ->where('employee_id', $employee_id)
                        ->whereHas(
                            'salaryItemsName', function (Builder $builder){
                                $builder->whereHas(
                                    'salaryItemsCategory', function (Builder $builder){
                                        $builder->where('id', 8);
                                    }
                                );
                            }
                        );
                }
            )
            ->get()
            ->sum('government_or_company_amount');

        return $company_contribution_value + $other_company_contribution;
    }

    public function company_contribution_value($employee_id, $company_id)
    {
        return DeductionDetails::query()
            ->whereHas(
                'employee_salary_item', function (Builder $builder) use ($employee_id, $company_id) {
                    $builder
                        ->where('company_id', $company_id)
                        ->where('employee_id', $employee_id)
                        ->whereHas(
                            'salaryItemsName', function (Builder $builder){
                                $builder->whereHas(
                                    'salaryItemsCategory', function (Builder $builder){
                                        $builder->where('id', 7);
                                    }
                                );
                            }
                        );
                }
            )
            ->get()
            ->sum('government_or_company_amount');
    }

    public function employee_contribution_value($employee_id, $company_id)
    {
        return DeductionDetails::query()
            ->whereHas(
                'employee_salary_item', function (Builder $builder) use ($employee_id, $company_id) {
                    $builder
                        ->where('company_id', $company_id)
                        ->where('employee_id', $employee_id)
                        ->whereHas(
                            'salaryItemsName', function (Builder $builder){
                                $builder->whereHas(
                                    'salaryItemsCategory', function (Builder $builder){
                                        $builder->where('id', 7);
                                    }
                                );
                            }
                        );
                }
            )
            ->get()
            ->sum('employee_amount');
    }

    public function other_company_deduction($employee_id, $company_id)
    {
        return DeductionDetails::query()
            ->whereHas(
                'employee_salary_item', function (Builder $builder) use ($employee_id, $company_id) {
                    $builder
                        ->where('company_id', $company_id)
                        ->where('employee_id', $employee_id)
                        ->whereHas(
                            'salaryItemsName', function (Builder $builder){
                                $builder->whereHas(
                                    'salaryItemsCategory', function (Builder $builder){
                                        $builder->where('id', 8);
                                    }
                                );
                            }
                        );
                }
            )
            ->get()
            ->sum('employee_amount');
    }

    public function other_company_contribution($employee_id, $company_id)
    {
        return DeductionDetails::query()
            ->whereHas(
                'employee_salary_item', function (Builder $builder) use ($employee_id, $company_id) {
                    $builder
                        ->where('company_id', $company_id)
                        ->where('employee_id', $employee_id)
                        ->whereHas(
                            'salaryItemsName', function (Builder $builder){
                                $builder->whereHas(
                                    'salaryItemsCategory', function (Builder $builder){
                                        $builder->where('id', 8);
                                    }
                                );
                            }
                        );
                }
            )
            ->get()
            ->sum('government_or_company_amount');
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
                ->get()
                ->sum('employee_amount');

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
                ->get()
                ->sum('employee_amount');
            return $employee_deduction + $other_complimentary_deduction;
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
            return EmployeeSalaryItem::query()
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
}
