<?php

namespace Modules\Payslip\Http\Services;

use Carbon\Carbon;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Modules\Attendance\Entities\Attendance;
use Modules\EmployeeSalaryItems\Entities\EmployeeSalaryItem;
use Modules\LeaveManagement\Entities\UserLeave;
use Modules\Payslip\Entities\PayslipDetail;

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

    public function add_payslip_details($payslip_id, $employee_id)
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
            PayslipDetail::create([
                'payslip_id' => $payslip_id,
                'salary_item_id' => $value->salary_item_id,
                'amount' => $value->amount,
            ]);
        }
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

    public function get_staff_deduction_sick_absent_amount($employee_id)
    {
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
                ->where('company_id', auth()->user()->company_id)
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

    public function government_deduction_amount($employee_id)
    {
        $employee_associated_amount = EmployeeSalaryItem::query()
            ->where('employee_id', $employee_id)
            ->whereHas(
                'salaryItemsName', function (Builder $builder) {
                    $builder->where('salary_items_category_id', 7);
                }
            )
            ->sum('amount');

        return $employee_associated_amount;
    }

    public function other_complimentary_deduction_amount($employee_id)
    {
        $employee_associated_amount = EmployeeSalaryItem::query()
            ->where('employee_id', $employee_id)
            ->whereHas(
                'salaryItemsName', function (Builder $builder) {
                    $builder->where('salary_items_category_id', 8);
                }
            )
            ->sum('amount');

        return $employee_associated_amount;
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
                $timeDifferenceMinutes = $outTime->diffInHours($inTime);
                $total_minutes += $timeDifferenceMinutes;
            }
        }
        return $total_minutes;
    }

    // employee-salary-items-calculation
    public function getAmountForEmployee($category_id, $employee_id)
    {
        if ($category_id == 7) {
            return [
                'employee_government_deduction' => 0.00,
                'other_complimentary_deduction' => 0.00,
                'employee_deduction' => 0.00,
            ];

            return DeductionDetails::query()
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
                ->get()->sum('employee_amount as emp_amount');

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
                ->where('employee_id', $employee_id)
                ->get()->sum('amount');
        }
    }
}
