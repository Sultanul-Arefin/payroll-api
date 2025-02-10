<?php

namespace Modules\Payslip\Http\Resources;

use Carbon\Carbon;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Database\Eloquent\Builder;
use JsonSerializable;
use Modules\Attendance\Entities\Attendance;
use Modules\LeaveManagement\Entities\UserLeave;
use Modules\LeaveManagement\Entities\UserLeaveDetail;
use Modules\Payslip\Entities\Payslip;
use Modules\Payslip\Entities\PayslipDetail;
use Modules\Payslip\Entities\PayslipDetailsForDeduction;
use Modules\SalaryItemsName\Entities\LeaveSalaryItems;

class ViewIndianPayslipResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'items_details' => $this->get_payslip_details($this->payslip_details),
            'total_earnings' => $this->pay_due_before_deduction,
            'payment_date' => $this->payment_date, //date('Y-m-d H:i:s')
            'fixed_pay_details' => $this->wages,
            'additional_pay' =>$this->additional_pay, // additional pay goes here
            'wage_deduction' => $this->leave_deduction,
            'total_fixed_pay' => $this->wages - $this->leave_deduction,
            'taxable_allowance' => $this->taxable_allowance,
            'non_taxable_allowance' => $this->non_taxable_allowance,
            'total_gross_pay' => (($this->wages + $this->additional_pay) - $this->leave_deduction) + $this->taxable_allowance + $this->non_taxable_allowance, // this is accurate            'tax_amount' => $this->tax_value + $this->post_tax_value,
            'taxable_gross_pay' => ((($this->wages + $this->additional_pay) - $this->leave_deduction) + $this->taxable_allowance + $this->non_taxable_allowance) - $this->non_taxable_allowance, // this is accurate // total_gross_pay - non_taxable_allowance            'pay_due_before_deduction' => $this->pay_due_before_deduction,
            //'staff_social_charges' => 0.00,
            'tax_amount' => $this->tax_value + $this->post_tax_value,
            'gross_pay_after_tax' => $this->gross_pay_after_tax,
            'pay_due_before_deduction' => $this->pay_due_before_deduction,
            'staff_social_charges' => $this->get_staff_social_charges(), // staff social charge goes here
            'total_net_pay' => $this->net_pay,
            'social_decution' => $this->get_social_deduction(),
            'other_decution' => $this->get_other_deduction(),
            'total_deductions' => $this->total_employee_deduction,
            'total_company_deduction' => $this->get_total_company_deduction(),
            'annual_leave' => $this->get_annual_leave_calculation($this->employee),
            // 'attendance' => $this->getTotalAttendanceDays(
            //     $this->employee->id,
            //     $this->start_date,
            //     $this->end_date
            // ),
            'net_salary' => $this->net_pay,
           // 'overall_calculation' => $this->overall_calculation(),
           'attendance'=>$this->getTotalWorkingDaysAttribute(),
        ];
    }

    // public function getTotalWorkingDaysAttribute()
    // {

    //     if ($this->hours_worked && $this->company && $this->company->working_hours_per_day) {
    //         return round($this->hours_worked / $this->company->working_hours_per_day, 2);
    //     }
    //     return null;
    // }


    public function get_annual_leave_calculation($employee)
    {
        return [
            'annual_leave_quota' => $this->getAnnualLeaveQuota(),
            'annual_leave_taken' => $this->getAnnualLeaveTaken($this->employee->id),
            // 'remaining_annual_leave' => $this->getAnnualLeaveQuota() - $this->getAnnualLeaveTaken($this->employee->id)
            'remaining_annual_leave' => $this->getAnnualLeaveQuota() - $this->getTotalAnnualLeaveTaken($this->employee->id),
            'remaining_sick_leave' => $this->getSickLeaveQuota() - $this->getTotalSickLeaveTaken($this->employee->id),
            'sick_leave_quota' => $this->getSickLeaveQuota(),
            'sick_leave_taken' => $this->getSickLeaveTaken($this->employee->id),
            // 'remaining_annual_leave' => $this->getAnnualLeaveQuota() - $this->getAnnualLeaveTaken($this->employee->id)
            // 'lop_days' => $this->getAbsentTaken($this->employee->id),
            // 'total_working_days'=>$this->getTotalAttendanceDays($this->employee->id, $this->first_date, $this->last_date),
            // 'paid_days' =>( $this->getAnnualLeaveTaken($this->employee->id)+$this->getSickLeaveTaken($this->employee->id))+$this->getTotalAttendanceDays($this->employee->id, $this->first_date, $this->last_date),
        ];
    }

    function getTotalAnnualLeaveTaken($user_id): mixed {
        $working_hours_per_day = auth()->user()->company?->working_hours_per_day;
        $payslip_year = date('Y', strtotime($this->first_date));
        $payslip_details = PayslipDetail::query()
                        ->whereHas(
                            'payslip', function(Builder $builder) use($user_id, $payslip_year){
                                $builder
                                    ->whereBetween('first_date', [
                                        date("${payslip_year}-1-1"), // Start of the year
                                        date("${payslip_year}-12-31"), // End of the year
                                    ])
                                    ->whereBetween('last_date', [
                                        date("${payslip_year}-1-1"), // Start of the year
                                        date("${payslip_year}-12-31"), // End of the year
                                    ])
                                    ->where('employee_id', $user_id)
                                    ->orderBy('created_at', 'ASC');
                            }
                        )
                        ->whereHas(
                            'employee_salary_item', function(Builder $builder){
                                $builder->whereHas(
                                    'salaryItemsName', function(Builder $builder){
                                        $builder->where('name', 'like', '%' . 'Holiday Rate' . '%');
                                    }
                                );
                            }
                        )
                        ->get();
        $total_annual_leave = 0;
        foreach($payslip_details as $value)
        {
            // Get "base_amount_or_hours" and remove "hours"
            $baseAmount = str_replace(' hours', '', $value->base_amount_or_hours);

            // Convert to numeric value
            $numericBaseAmount = (float)$baseAmount;

            $total_annual_leave += $numericBaseAmount;

            // Calculate the value (base * rate)
            // $calculatedValue = $numericBaseAmount * (float)$leave['rate'];
        }
        return ceil($total_annual_leave / $working_hours_per_day);
    }

    function getAnnualLeaveTaken($user_id): mixed {
        // $payslip_details = $this->payslip_details;
        $working_hours_per_day = auth()->user()->company?->working_hours_per_day;
        $payslip_details = PayslipDetail::query()
                        ->whereHas(
                            'payslip', function(Builder $builder){
                                $builder->where('id', $this->id);
                            }
                        )
                        ->whereHas(
                            'employee_salary_item', function(Builder $builder){
                                $builder->whereHas(
                                    'salaryItemsName', function(Builder $builder){
                                        $builder->where('name', 'like', '%' . 'Holiday Rate' . '%');
                                    }
                                );
                            }
                        )
                        ->get();
        $total_annual_leave = 0;
        foreach($payslip_details as $value)
        {
            // Get "base_amount_or_hours" and remove "hours"
            $baseAmount = str_replace(' hours', '', $value->base_amount_or_hours);

            // Convert to numeric value
            $numericBaseAmount = (float)$baseAmount;

            $total_annual_leave += $numericBaseAmount;

            // Calculate the value (base * rate)
            // $calculatedValue = $numericBaseAmount * (float)$leave['rate'];
        }
        return ceil($total_annual_leave / $working_hours_per_day);

    }

    function getAnnualLeaveQuota(): int {
        $data = LeaveSalaryItems::query()
            ->whereHas(
                'salary_items_name', function(Builder $builder){
                    $builder
                    ->where(
                        'name',
                        'Annual Leave'
                    )->where(
                        'company_id',
                        auth()->user()->company_id
                    );
                }
            )
            ->first();
        return $data->no_of_days;
    }

    function getTotalSickLeaveTaken($user_id): mixed {
        $working_hours_per_day = auth()->user()->company?->working_hours_per_day;
        $payslip_year = date('Y', strtotime($this->first_date));
        $payslip_details = PayslipDetail::query()
                        ->whereHas(
                            'payslip', function(Builder $builder) use($user_id, $payslip_year){
                                $builder
                                    ->whereBetween('first_date', [
                                        date("${payslip_year}-1-1"), // Start of the year
                                        date("${payslip_year}-12-31"), // End of the year
                                    ])
                                    ->whereBetween('last_date', [
                                        date("${payslip_year}-1-1"), // Start of the year
                                        date("${payslip_year}-12-31"), // End of the year
                                    ])
                                    ->where('employee_id', $user_id)
                                    ->orderBy('created_at', 'ASC');
                            }
                        )
                        ->whereHas(
                            'employee_salary_item', function(Builder $builder){
                                $builder->whereHas(
                                    'salaryItemsName', function(Builder $builder){
                                        $builder->where('name', 'like', '%' . 'Paid Sick Leave Rate' . '%');
                                    }
                                );
                            }
                        )
                        ->get();
        $total_sick_leave = 0;
        foreach($payslip_details as $value)
            {
                // Get "base_amount_or_hours" and remove "hours"
                $baseAmount = str_replace(' hours', '', $value->base_amount_or_hours);

                // Convert to numeric value
                $numericBaseAmount = (float)$baseAmount;

                $total_sick_leave += $numericBaseAmount;

                // Calculate the value (base * rate)
                // $calculatedValue = $numericBaseAmount * (float)$leave['rate'];
            }
            return ceil($total_sick_leave / $working_hours_per_day);
        }

    function getSickLeaveTaken($user_id): mixed {
        // $payslip_details = $this->payslip_details;
        $working_hours_per_day = auth()->user()->company?->working_hours_per_day;
        $payslip_details = PayslipDetail::query()
                        ->whereHas(
                            'payslip', function(Builder $builder){
                                $builder->where('id', $this->id);
                            }
                        )
                        ->whereHas(
                            'employee_salary_item', function(Builder $builder){
                                $builder->whereHas(
                                    'salaryItemsName', function(Builder $builder){
                                        $builder->where('name', 'like', '%' . 'Paid Sick Leave Rate' . '%');
                                    }
                                );
                            }
                        )
                        ->get();
        $total_sick_leave = 0;
        foreach($payslip_details as $value)
            {
                // Get "base_amount_or_hours" and remove "hours"
                $baseAmount = str_replace(' hours', '', $value->base_amount_or_hours);

                // Convert to numeric value
                $numericBaseAmount = (float)$baseAmount;

                $total_sick_leave += $numericBaseAmount;

                // Calculate the value (base * rate)
                // $calculatedValue = $numericBaseAmount * (float)$leave['rate'];
            }
            return ceil($total_sick_leave / $working_hours_per_day);

    }

    function getSickLeaveQuota(): int {
        $data = LeaveSalaryItems::query()
            ->whereHas(
                'salary_items_name', function(Builder $builder){
                    $builder
                    ->where(
                        'name',
                        'Sick Leave'
                    )->where(
                        'company_id',
                        auth()->user()->company_id
                    );
                }
            )
            ->first();
        return $data->no_of_days;
    }

    function getAbsentTaken($user_id): mixed {
        // $payslip_details = $this->payslip_details;
        $working_hours_per_day = auth()->user()->company?->working_hours_per_day;
        $payslip_details = PayslipDetail::query()
                        ->whereHas(
                            'payslip', function(Builder $builder){
                                $builder->where('id', $this->id);
                            }
                        )
                        ->whereHas(
                            'employee_salary_item', function(Builder $builder){
                                $builder->whereHas(
                                    'salaryItemsName', function(Builder $builder){
                                        $builder->where('name', 'like', '%' . 'Absent' . '%');
                                    }
                                );
                            }
                        )
                        ->get();
        $total_lop_day = 0;
        foreach($payslip_details as $value)
        {
            // Get "base_amount_or_hours" and remove "hours"
            $baseAmount = str_replace(' hours', '', $value->base_amount_or_hours);

            // Convert to numeric value
            $numericBaseAmount = (float)$baseAmount;

            $total_lop_day += $numericBaseAmount;

            // Calculate the value (base * rate)
            // $calculatedValue = $numericBaseAmount * (float)$leave['rate'];
        }
        return ceil($total_lop_day / $working_hours_per_day);

    }



    // public function getTotalAttendanceDays($employee_id, $from_date, $to_date)
    //             {


    //                     if (!$from_date) {
    //                         $from_date = $this->first_date;
    //                     }
    //                     if (!$to_date) {
    //                         $to_date = $this->last_date;
    //                     }


    //                 $present_days = Attendance::query()
    //                     ->where('user_id', $employee_id)
    //                     ->whereBetween('dates', [$from_date, $to_date])
    //                     ->where('status', Attendance::PRESENT) // Only count "Present" days
    //                     ->count();
    //                    // return $present_days;

    //                     $lop_days=$this->getAbsentTaken($this->employee->id);
    //                     $total_leave_taken=$this->getAnnualLeaveTaken($this->employee->id)+$this->getSickLeaveTaken($this->employee->id);
    //                     $paid_days=$total_leave_taken+$present_days;

    //                     return
    //                                 [
    //                                     'total_working_days' => $present_days,
    //                                     'lop_days' =>  $lop_days,
    //                                     'leaves_taken' =>$total_leave_taken,
    //                                     'paid_days' =>$paid_days,
    //                                 ];
    //            }


        // public function getTotalWorkingDaysAttribute()
        //        {
        //            if (!$this->hours_worked || !$this->company || !$this->company->working_hours_per_day) {
        //                return null;
        //            }

        //            $lop_days = $this->getAbsentTaken($this->employee->id);
        //            $total_leave_taken = $this->getAnnualLeaveTaken($this->employee->id) + $this->getSickLeaveTaken($this->employee->id);
        //            $total_working_days = round($this->hours_worked / $this->company->working_hours_per_day, 2);
        //            $paid_days = $total_leave_taken + $total_working_days;

        //            return [
        //                'total_working_days' => $total_working_days,
        //                'lop_days' => $lop_days,
        //                'leaves_taken' => $total_leave_taken,
        //                'paid_days' => $paid_days,
        //            ];
        // }

        public function getTotalWorkingDaysAttribute()
            {

                    if (!$this->hours_worked || !$this->company || !$this->company->working_hours_per_day) {
                        $total_working_days = 0;
                    } else {
                        $total_working_days = round($this->hours_worked / ($this->company->working_hours_per_day+$this->company->lunch_and_others_per_day), 2);
                    }

                    $lop_days = $this->getAbsentTaken($this->employee->id);
                    $total_leave_taken = $this->getAnnualLeaveTaken($this->employee->id) + $this->getSickLeaveTaken($this->employee->id);
                    $paid_days = $total_leave_taken + $total_working_days;

                    return [
                        'total_working_days' => $total_working_days,
                        'lop_days' => $lop_days,
                        'leaves_taken' => $total_leave_taken,
                        'paid_days' => $paid_days,
                    ];
            }





        public function get_other_deduction()
        {
            $deduction_details = PayslipDetailsForDeduction::query()
                                ->whereHas(
                                    'salary_item_name', function(Builder $builder){
                                        $builder->where('salary_items_category_id', 8);
                                    }
                                )
                                ->where('payslip_id', $this->id)
                                ->get();
            $response = [];
            $total_company_amount = 0;
            $total_employee_amount=0;
            foreach($deduction_details as $value)
            {
                $total_company_amount += $value->government_or_company_amount;
                $total_employee_amount += $value->employee_amount;
                array_push($response, [
                    'title' => $value?->salary_item_name?->name,
                    'base' => $this->gross_pay_before_tax,
                    'employee_rate' => $value->employee_amount_rate,
                    'employee_amount' => $value->employee_amount,
                    'company_rate' => $value->government_or_company_amount_rate,
                    'company_amount' => $value->government_or_company_amount
                ]);
            }
            return [
                'deductions' => $response,
                'total_company_amount' => $total_company_amount,
                'total_employee_amount' => $total_employee_amount,
            ];
        }

        public function get_social_deduction()
        {
            $deduction_details = PayslipDetailsForDeduction::query()
                                ->whereHas(
                                    'salary_item_name', function(Builder $builder){
                                        $builder->where('salary_items_category_id', 7);
                                    }
                                )
                                ->where('payslip_id', $this->id)
                                ->get();
            $response = [];
            $total_company_amount = 0;
            $total_employee_amount=0;

            foreach($deduction_details as $value)
            {
                $total_company_amount += $value->government_or_company_amount;
                $total_employee_amount += $value->employee_amount;

                array_push($response, [
                    'title' => $value?->salary_item_name?->name,
                    'base' => $this->gross_pay_before_tax,
                    'employee_rate' => $value->employee_amount_rate,
                    'employee_amount' => $value->employee_amount,
                    'company_rate' => $value->government_or_company_amount_rate,
                    'company_amount' => $value->government_or_company_amount
                ]);
            }
            return [
                'deductions' => $response,
                'total_company_amount' => $total_company_amount,
                'total_employee_amount' => $total_employee_amount,
            ];
        }

        public function get_total_company_deduction()
            {
                $other_deduction = $this->get_other_deduction();
                $social_deduction = $this->get_social_deduction();

                $total_company_amount = $other_deduction['total_company_amount'] + $social_deduction['total_company_amount'];
                $total_employee_amount = $other_deduction['total_employee_amount'] + $social_deduction['total_employee_amount'];

                return [
                    'total_company_amount' => $total_company_amount,
                    'total_employee_amount' => $total_employee_amount,
                ];
            }

    public function get_staff_social_charges()
    {
        return PayslipDetailsForDeduction::query()
                ->where('payslip_id', $this->id)
                ->sum('employee_amount');
    }

    // public function get_payslip_details($payslip_details){
    //     $payslip_value = 0;
    //     foreach($payslip_details as $payslip_detail){
    //         $payslip_detail->pay_details = $payslip_detail->salary_item->name;
    //         $payslip_detail->base_amount_or_hours = $payslip_detail->base_amount_or_hours;
    //         $payslip_detail->rate = $payslip_detail->rate;
    //         $payslip_detail->amount = $payslip_detail->amount;
    //         if (in_array($payslip_detail->salary_item->name, ["Bonus", "Overtime Rate", "Double Overtime Rate"])) {
    //             $payslip_detail->category_id = $payslip_detail?->salary_item?->salaryItemsCategory?->id . "_additional";
    //         } else {
    //             $payslip_detail->category_id = $payslip_detail?->salary_item?->salaryItemsCategory?->id;
    //         }
    //         $payslip_value += $payslip_detail->amount;

    //         // unset these keys from the response
    //         unset($payslip_detail->salary_item, $payslip_detail->id, $payslip_detail->created_at, $payslip_detail->updated_at, $payslip_detail->payslip_id, $payslip_detail->salary_item_id);
    //     }
    //    // return $payslip_details;
    //    return [
    //     'payslip_details' => $payslip_details,
    //     //'total_earnings' => $payslip_value
    // ];
    // }

    public function get_payslip_details($payslip_details){
        $payslip_value = 0;
        // Filter out rows where salary_item->name is "Annual Leave" or "Sick Leave"
        $filtered_details = $payslip_details->filter(function ($payslip_detail) {
            return !in_array($payslip_detail->salary_item->name, ['Annual Leave', 'Sick Leave']);
        });

        foreach($filtered_details as $payslip_detail){
            // if($payslip_detail->salary_item->name == "Annual Leave" || $payslip_detail->salary_item->name == "Annual Leave"){

            // } else{
                $payslip_detail->pay_details = $payslip_detail->salary_item->name;
                $payslip_detail->base_amount_or_hours = $this->get_base_amount_or_hours($payslip_detail->base_amount_or_hours);

                $payslip_detail->rate = $payslip_detail->rate;
                $payslip_detail->amount = $payslip_detail->amount;
                if (in_array($payslip_detail->salary_item->name, ["Bonus", "Overtime Rate", "Double Overtime Rate"])) {
                    $payslip_detail->category_id = $payslip_detail?->salary_item?->salaryItemsCategory?->id . "_additional";
                } else {
                    $payslip_detail->category_id = $payslip_detail?->salary_item?->salaryItemsCategory?->id;
                }
            // }
            $payslip_value += $payslip_detail->amount;

            // unset these keys from the response
            unset($payslip_detail->salary_item, $payslip_detail->id, $payslip_detail->created_at, $payslip_detail->updated_at, $payslip_detail->payslip_id, $payslip_detail->salary_item_id);
        }
        return [
            'payslip_details' => $filtered_details->values(),
            //'total_earnings' => $payslip_value
        ];
    }

    public function get_base_amount_or_hours($get_base_amount_or_hours){
        $working_hours_per_day = auth()->user()->company?->working_hours_per_day;
        if (preg_match('/(\d+)\s*hours?/i', $get_base_amount_or_hours, $matches)) {
            $numericValue = (int)$matches[1];
            $result = $numericValue / $working_hours_per_day;
            return $get_base_amount_or_hours . "(" . number_format($result, 2) . " day(s))";
        } else {
            return $get_base_amount_or_hours;
        }
    }

    public function getOvertimeHours($payslip_details)
    {
        $overtime = 0;
        foreach($payslip_details as $value)
        {
            if ($value->pay_details == "Overtime Rate") {
                $overtime = $value?->base_amount_or_hours;
            }
        }
        return $overtime;
    }

    public function getTotalOvertimeHours($payslip_details)
    {
        $working_hours_per_day = auth()->user()->company?->working_hours_per_day;
        $payslip_year = date('Y', strtotime($this->first_date));

        $user_id = $this->employee_id;
        $payslip_details = PayslipDetail::query()
                        ->whereHas(
                            'payslip', function(Builder $builder) use($user_id, $payslip_year){
                                $builder
                                    ->whereBetween('first_date', [
                                        date("$payslip_year-1-1"), // Start of the year
                                        date("$payslip_year-12-31"), // End of the year
                                    ])
                                    ->whereBetween('last_date', [
                                        date("$payslip_year-1-1"), // Start of the year
                                        date("$payslip_year-12-31"), // End of the year
                                    ])
                                    ->where('employee_id', $user_id)
                                    ->orderBy('created_at', 'ASC');
                            }
                        )
                        ->whereHas(
                            'employee_salary_item', function(Builder $builder){
                                $builder->whereHas(
                                    'salaryItemsName', function(Builder $builder){
                                        $builder->where('name', 'like', '%' . 'Overtime Rate' . '%');
                                    }
                                );
                            }
                        )
                        ->get();
        $total_overtime_hours = 0;
        foreach($payslip_details as $value)
        {
            // Get "base_amount_or_hours" and remove "hours"
            $baseAmount = str_replace(' hours', '', $value->base_amount_or_hours);

            // Convert to numeric value
            $numericBaseAmount = (float)$baseAmount;

            $total_overtime_hours += $numericBaseAmount;

            // Calculate the value (base * rate)
            // $calculatedValue = $numericBaseAmount * (float)$leave['rate'];
        }
        return ceil($total_overtime_hours / $working_hours_per_day);
    }


    public function overall_calculation()
    {
        return [
            'monthly' => [
                [
                    'hours' => $this->hours_worked,
                    'overtime_hours' => 0, // calculate full working hours & get overtime
                    'total_fixed_pay' => $this->wages - $this->leave_deduction,
                    'taxable_allowances' => $this->taxable_allowance,
                    'non_taxable_allowances' => $this->non_taxable_allowance,
                    'total_gross_pay' => $this->gross_pay_before_tax,
                    'taxable_gross_pay' => $this->gross_pay_before_tax - $this->non_taxable_allowance,
                    'ytd_tax_paid' => 0,
                    'total_staff_contribution' => $this->total_employee_deduction,
                    'total_company_contribution' => $this->company_contribution,
                    'total_staff_cost' => $this->net_pay,
                    'total_net_pay' => $this->net_pay,
                ],
            ],
            'yearly' => [
                [
                    'hours' => $this->hours_worked,
                    'overtime_hours' => 0,
                    'total_fixed_pay' => $this->wages - $this->leave_deduction,
                    'taxable_allowances' => $this->taxable_allowance,
                    'non_taxable_allowances' => $this->non_taxable_allowance,
                    'total_gross_pay' => $this->gross_pay_before_tax,
                    'taxable_gross_pay' => $this->gross_pay_before_tax - $this->non_taxable_allowance,
                    'ytd_tax_paid' => 0,
                    'total_staff_contribution' => $this->total_employee_deduction,
                    'total_company_contribution' => $this->company_contribution,
                    'total_staff_cost' => $this->net_pay,
                    'total_net_pay' => $this->net_pay,
                ],
            ],
            'total_net_pay' => $this->net_pay,
        ];
    }
}
