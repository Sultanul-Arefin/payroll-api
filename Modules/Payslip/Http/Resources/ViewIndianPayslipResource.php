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
            'total_earnings' => round(floatval($this->pay_due_before_deduction),2),
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
           // 'total_net_pay' => round(floatval($this->net_pay),2),
           'total_net_pay' =>$this->get_netPay($this->wages,$this->hours_worked,$this->net_pay),
            'social_decution' => $this->get_social_deduction(),
            'other_decution' => $this->get_other_deduction(),
            'total_deductions' => $this->total_employee_deduction,
            'total_company_deduction' => $this->get_total_company_deduction(),//sub total
            'annual_leave' => $this->get_annual_leave_calculation($this->employee),
            // 'attendance' => $this->getTotalAttendanceDays(
            //     $this->employee->id,
            //     $this->start_date,
            //     $this->end_date
            // ),
            'net_salary' => round(floatval($this->net_pay),2),
           // 'overall_calculation' => $this->overall_calculation(),
           'attendance'=>$this->getTotalWorkingDaysAttribute(),
        ];
    }

    public function get_netPay($wages,$hours_worked,$net_pay){
        if ($wages<=0 && $hours_worked<=0 ){
            return 0;
        }else {
            return round(floatval($net_pay),2);
        }
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





    // public function get_other_deduction()
    // {
    //     $deduction_details = PayslipDetailsForDeduction::query()
    //                             ->whereHas(
    //                                 'salary_item_name', function(Builder $builder){
    //                                     $builder->where('salary_items_category_id', 8);
    //                                 }
    //                             )
    //                             ->where('payslip_id', $this->id)
    //                             ->get();
    //     $response = [];
    //     $total_company_amount = 0;
    //     $total_employee_amount=0;
    //     foreach($deduction_details as $value)
    //         {
    //             $total_company_amount += $value->government_or_company_amount;
    //             $total_employee_amount += $value->employee_amount;
    //             array_push($response, [
    //                 'title' => $value?->salary_item_name?->name,
    //                 'base' => $this->gross_pay_before_tax,
    //                 'employee_rate' => $value->employee_amount_rate,
    //                 'employee_amount' => $value->employee_amount,
    //                 'company_rate' => $value->government_or_company_amount_rate,
    //                 'company_amount' => $value->government_or_company_amount
    //             ]);
    //         }
    //         return [
    //             'deductions' => $response,
    //             'total_company_amount' => $total_company_amount,
    //             'total_employee_amount' => $total_employee_amount,
    //         ];
    // }

    public function get_other_deduction()
    {
        $deduction_details = PayslipDetailsForDeduction::query()
            ->whereHas('salary_item_name', function (Builder $builder) {
                $builder->where('salary_items_category_id', 8);
            })
            ->where('payslip_id', $this->id)
            ->get();

        $response = [];
        $total_company_amount = 0;
        $total_employee_amount = 0;
        $yearly_total_company_amount = 0;
        $yearly_total_employee_amount = 0;

        $current_month = date('m', strtotime(now()));

        $yearly_payslip_data = $this->get_yearly_Other_deductions($this->employee_id, $current_month);

        foreach ($deduction_details as $value) {
            $title = $value?->salary_item_name?->name ?? 'N/A';

            // Yearly Total = January to current month total
            $yearly_total = $yearly_payslip_data[$title] ?? ['employee' => 0, 'company' => 0];

            // Cast amounts to float for calculation and formatting
            $employee_amount = (float) $value->employee_amount;
            $company_amount = (float) $value->government_or_company_amount;
            $yearly_employee_total = (float) $yearly_total['employee'];
            $yearly_company_total = (float) $yearly_total['company'];

            // Update total amounts
            $total_company_amount += $company_amount;
            $total_employee_amount += $employee_amount;
            $yearly_total_company_amount += $yearly_company_total;
            $yearly_total_employee_amount += $yearly_employee_total;

            array_push($response, [
                'title' => $title,
                'base' => $this->gross_pay_before_tax,
                'employee_rate' => $value->employee_amount_rate,
                'employee_amount' => round(floatval($employee_amount), 2),
                'company_rate' => $value->government_or_company_amount_rate,
                'company_amount' => round(floatval($company_amount), 2),
                'yearly_total_employee_amount' => round(floatval($yearly_employee_total), 2),
                'yearly_total_company_amount' => round(floatval($yearly_company_total), 2),
            ]);
        }

        // After the loop, format the totals
        $total_company_amount = round(floatval($total_company_amount), 2);
        $total_employee_amount = round(floatval($total_employee_amount), 2);
        $yearly_total_company_amount = round(floatval($yearly_total_company_amount), 2);
        $yearly_total_employee_amount = round(floatval($yearly_total_employee_amount), 2);

        // Final response with formatted totals
        return [
            'deductions' => $response,
            'total_company_amount' => $total_company_amount,
            'total_employee_amount' => $total_employee_amount,
            'yearly_total_company_amount' => $yearly_total_company_amount,
            'yearly_total_employee_amount' => $yearly_total_employee_amount,
        ];
    }

    public function get_yearly_Other_deductions($employee_id, $current_month, $category_id = 8)
    {
        // First, get the payslip's actual year and month using $this->first_date
        $current_year = date('Y', strtotime($this->first_date));
        $current_month = date('m', strtotime($this->first_date));

        // Query the payslip deductions up to the current month of that year
        $yearly_payslips = PayslipDetailsForDeduction::query()
            ->whereHas('payslip', function ($query) use ($employee_id, $current_year, $current_month) {
                $query->where('employee_id', $employee_id)
                    ->whereYear('first_date', $current_year)
                    ->whereMonth('first_date', '<=', $current_month);
            })
            ->whereHas('salary_item_name', function ($query) use ($category_id) {
                $query->where('salary_items_category_id', $category_id);
            })
            ->get();

        $yearly_totals = [];

        foreach ($yearly_payslips as $payslip) {
            $pay_detail_name = $payslip->salary_item_name->name ?? 'N/A';

            if (!isset($yearly_totals[$pay_detail_name])) {
                $yearly_totals[$pay_detail_name] = [
                    'employee' => 0,
                    'company' => 0,
                ];
            }

            $yearly_totals[$pay_detail_name]['employee'] += (float) $payslip->employee_amount;
            $yearly_totals[$pay_detail_name]['company'] += (float) $payslip->government_or_company_amount;
        }

        return $yearly_totals;
    }

    

    // public function get_social_deduction()
    // {
    //     $deduction_details = PayslipDetailsForDeduction::query()
    //                         ->whereHas(
    //                             'salary_item_name', function(Builder $builder){
    //                                 $builder->where('salary_items_category_id', 7);
    //                             }
    //                         )
    //                         ->where('payslip_id', $this->id)
    //                         ->get();
    //     $response = [];
    //     $total_company_amount = 0;
    //     $total_employee_amount=0;

    //     foreach($deduction_details as $value)
    //     {
    //             $total_company_amount += $value->government_or_company_amount;
    //             $total_employee_amount += $value->employee_amount;

    //             array_push($response, [
    //                 'title' => $value?->salary_item_name?->name,
    //                 'base' => $this->gross_pay_before_tax,
    //                 'employee_rate' => $value->employee_amount_rate,
    //                 'employee_amount' => $value->employee_amount,
    //                 'company_rate' => $value->government_or_company_amount_rate,
    //                 'company_amount' => $value->government_or_company_amount
    //             ]);
    //     }
    //         return [
    //             'deductions' => $response,
    //             'total_company_amount' => $total_company_amount,
    //             'total_employee_amount' => $total_employee_amount,
    //         ];
    // }

    public function get_social_deduction()
    {
        $deduction_details = PayslipDetailsForDeduction::query()
            ->whereHas('salary_item_name', function (Builder $builder) {
                $builder->where('salary_items_category_id', 7);
            })
            ->where('payslip_id', $this->id)
            ->get();

        $response = [];
        $total_company_amount = 0;
        $total_employee_amount = 0;
        $yearly_total_company_amount = 0;
        $yearly_total_employee_amount = 0;

        
        $current_month = date('m', strtotime(now()));

        // Yearly total to current_month 
        $yearly_payslip_data = $this->get_yearly_deduction_details($this->employee_id, $current_month);
        

        foreach ($deduction_details as $value) {
            $title = $value?->salary_item_name?->name ?? 'N/A';
        
            $yearly_total = $yearly_payslip_data[$title] ?? ['employee' => 0, 'company' => 0];
        
            // Ensure these values are properly initialized as floats
            $employee_amount = (float) $value->employee_amount; 
            $company_amount = (float) $value->government_or_company_amount;
            $yearly_employee_total = (float) $yearly_total['employee'];
            $yearly_company_total = (float) $yearly_total['company'];
        
            // Update total amounts
            $total_company_amount += $company_amount;
            $total_employee_amount += $employee_amount;
            $yearly_total_company_amount += $yearly_company_total;
            $yearly_total_employee_amount += $yearly_employee_total;
        
            // Add formatted response data
            array_push($response, [
                'title' => $title,
                'base' => $this->gross_pay_before_tax,
                'employee_rate' => $value->employee_amount_rate,
                'employee_amount' => round(floatval($employee_amount), 2),
                'company_rate' => $value->government_or_company_amount_rate,
                'company_amount' => round(floatval($company_amount), 2),
                'yearly_total_employee_amount' => round(floatval($yearly_employee_total), 2),
                'yearly_total_company_amount' => round(floatval($yearly_company_total), 2),
            ]);
        }
        
        // After the loop, format the totals
        $total_company_amount = round(floatval($total_company_amount), 2);
        $total_employee_amount = round(floatval($total_employee_amount), 2);
        $yearly_total_company_amount = round(floatval($yearly_total_company_amount), 2);
        $yearly_total_employee_amount = round(floatval($yearly_total_employee_amount), 2);
        
        // Final response with formatted totals
        return [
            'deductions' => $response,
            'total_company_amount' => $total_company_amount,
            'total_employee_amount' => $total_employee_amount,
            'yearly_total_company_amount' => $yearly_total_company_amount,
            'yearly_total_employee_amount' => $yearly_total_employee_amount,
        ];
    }

    public function get_yearly_deduction_details($employee_id, $current_month, $category_id = 7)
    {
        // First, get the payslip's actual year and month using $this->first_date
        $current_year = date('Y', strtotime($this->first_date));
        $current_month = date('m', strtotime($this->first_date));

        // Query the payslip deductions up to the current month of that year
        $yearly_payslips = PayslipDetailsForDeduction::query()
            ->whereHas('payslip', function ($query) use ($employee_id, $current_year, $current_month) {
                $query->where('employee_id', $employee_id)
                    ->whereYear('first_date', $current_year)
                    ->whereMonth('first_date', '<=', $current_month);
            })
            ->whereHas('salary_item_name', function ($query) use ($category_id) {
                $query->where('salary_items_category_id', $category_id);
            })
            ->get();

        $yearly_totals = [];

        foreach ($yearly_payslips as $payslip) {
            $pay_detail_name = $payslip->salary_item_name->name ?? 'N/A';

            if (!isset($yearly_totals[$pay_detail_name])) {
                $yearly_totals[$pay_detail_name] = [
                    'employee' => 0,
                    'company' => 0,
                ];
            }

            $yearly_totals[$pay_detail_name]['employee'] += (float) $payslip->employee_amount;
            $yearly_totals[$pay_detail_name]['company'] += (float) $payslip->government_or_company_amount;
        }

        return $yearly_totals;
    }

    public function get_total_company_deduction()
    {
            $other_deduction = $this->get_other_deduction();
            $social_deduction = $this->get_social_deduction();

            $total_company_amount = $other_deduction['total_company_amount'] + $social_deduction['total_company_amount'];
            $total_employee_amount = $other_deduction['total_employee_amount'] + $social_deduction['total_employee_amount'];
            $yearly_total_company_amount = $other_deduction['yearly_total_company_amount'] + $social_deduction['yearly_total_company_amount'];
            $yearly_total_employee_amount = $other_deduction['yearly_total_employee_amount'] + $social_deduction['yearly_total_employee_amount'];

            return [
                'total_company_amount' =>round(floatval($total_company_amount),2),
                'total_employee_amount' => round(floatval($total_employee_amount),2),
                'yearly_total_company_amount' => round(floatval($yearly_total_company_amount),2),
                'yearly_total_employee_amount' => round(floatval($yearly_total_employee_amount),2),
            ];
    }


    // public function get_total_company_deduction()
    // {
    //         $other_deduction = $this->get_other_deduction();
    //         $social_deduction = $this->get_social_deduction();

    //         $total_company_amount = $other_deduction['total_company_amount'] + $social_deduction['total_company_amount'];
    //         $total_employee_amount = $other_deduction['total_employee_amount'] + $social_deduction['total_employee_amount'];

    //         return [
    //             'total_company_amount' => round(floatval($total_company_amount),2),
    //             'total_employee_amount' => round(floatval($total_employee_amount),2),
    //         ];
    // }

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

    // public function get_payslip_details($payslip_details){
    //     $payslip_value = 0;
    //     // Filter out rows where salary_item->name is "Annual Leave" or "Sick Leave"
    //     $filtered_details = $payslip_details->filter(function ($payslip_detail) {
    //         return !in_array($payslip_detail->salary_item->name, ['Annual Leave', 'Sick Leave']);
    //     });

    //     foreach($filtered_details as $payslip_detail){
    //         // if($payslip_detail->salary_item->name == "Annual Leave" || $payslip_detail->salary_item->name == "Annual Leave"){

    //         // } else{
    //             $payslip_detail->pay_details = $payslip_detail->salary_item->name;
    //             $payslip_detail->base_amount_or_hours = $this->get_base_amount_or_hours($payslip_detail->base_amount_or_hours);

    //             $payslip_detail->rate = $payslip_detail->rate;
    //             $payslip_detail->amount = $payslip_detail->amount;
    //             if (in_array($payslip_detail->salary_item->name, ["Bonus", "Overtime Rate", "Double Overtime Rate"])) {
    //                 $payslip_detail->category_id = $payslip_detail?->salary_item?->salaryItemsCategory?->id . "_additional";
    //             } else {
    //                 $payslip_detail->category_id = $payslip_detail?->salary_item?->salaryItemsCategory?->id;
    //             }
    //         // }
    //         $payslip_value += $payslip_detail->amount;

    //         // unset these keys from the response
    //         unset($payslip_detail->salary_item, $payslip_detail->id, $payslip_detail->created_at, $payslip_detail->updated_at, $payslip_detail->payslip_id, $payslip_detail->salary_item_id);
    //     }
    //     return [
    //         'payslip_details' => $filtered_details->values(),
    //         //'total_earnings' => $payslip_value
    //     ];
    // }

    public function get_payslip_details($payslip_details)
    {
            $payslip_value = 0;
            $filteredDetails = [];
        
            if ($payslip_details->isEmpty()) {
                return [
                    'payslip_details' => [],
                    'total_payslip_value_employee' => 0,
                ];
            }
        
            $current_month = date('m', strtotime($payslip_details->first()->payslip->first_date));
        
            $yearly_payslip_data = $this->get_yearly_payslip_totals($this->employee_id, $current_month);
        
            $filtered_details = $payslip_details->filter(function ($payslip_detail) {
                return !in_array($payslip_detail->salary_item->name, ['Annual Leave', 'Sick Leave']);
            });
        
            foreach ($filtered_details as $payslip_detail) {
                $pay_detail_name = $payslip_detail->salary_item->name ?? 'N/A';
        
                $payslip_detail->pay_details = $pay_detail_name;
                $payslip_detail->base_amount_or_hours = $this->get_base_amount_or_hours($payslip_detail->base_amount_or_hours) ?? 0;
                $payslip_detail->rate = $payslip_detail->rate ?? 0;
                $payslip_detail->amount = $payslip_detail->amount ?? 0;
        
                $previous_total = $yearly_payslip_data[$pay_detail_name] ?? 0;
                $payslip_detail->yearly_total = $previous_total;
        
                if (in_array($pay_detail_name, ["Bonus", "Overtime Rate", "Double Overtime Rate"])) {
                    $payslip_detail->category_id = $payslip_detail?->salary_item?->salaryItemsCategory?->id . "_additional";
                } else {
                    $payslip_detail->category_id = $payslip_detail?->salary_item?->salaryItemsCategory?->id;
                }
        
                // Skip category_id 5 and 6
                if (in_array($payslip_detail->category_id, [5, 6])) {
                    continue;
                }
        
                $payslip_value += $payslip_detail->amount;
        
                // Unset unnecessary keys
                unset(
                    $payslip_detail->salary_item,
                    $payslip_detail->id,
                    $payslip_detail->created_at,
                    $payslip_detail->updated_at,
                    $payslip_detail->payslip_id,
                    $payslip_detail->salary_item_id,
                    $payslip_detail->payslip
                );
        
                $filteredDetails[] = $payslip_detail;
            }
        
            return [
                'payslip_details' => $filteredDetails,
                'total_payslip_value_employee' => $payslip_value,
                'total' => $this->get_yearly_data("total_gross_pay")['total_gross_pay'],
            ];
    }

    public function get_yearly_payslip_totals($employee_id, $current_month)
    {
            $current_year = date('Y');
        
            // Get all payslips from January to the selected month
            $yearly_payslips = PayslipDetail::query()
                ->whereHas('payslip', function ($query) use ($employee_id, $current_year, $current_month) {
                    $query->where('employee_id', $employee_id)
                        ->whereYear('first_date', $current_year)
                        ->whereMonth('first_date', '<=', $current_month); // Only up to selected month
                })
                ->get();
        
            $yearly_totals = [];
        
            foreach ($yearly_payslips as $payslip) {
                $pay_detail_name = $payslip->salary_item->name;
        
                preg_match('/(\d+)/', $payslip->base_amount_or_hours, $matches);
                $months = isset($matches[1]) ? (int)$matches[1] : 1;
        
                $amount = $months * (float) $payslip->rate;
        
                // Accumulate totals for each salary item
                if (!isset($yearly_totals[$pay_detail_name])) {
                    $yearly_totals[$pay_detail_name] = 0;
                }
        
                $yearly_totals[$pay_detail_name] += $amount;
            }
        
            return $yearly_totals;
    }
    

    public function get_base_amount_or_hours($get_base_amount_or_hours){
        $working_hours_per_day = auth()->user()->company?->working_hours_per_day;
        if (preg_match('/(\d+(\.\d+)?)\s*hours?/i', $get_base_amount_or_hours, $matches)) {
            $numericValue = (float)$matches[1]; // Convert to float to keep decimals
            $result = $numericValue / $working_hours_per_day;
            return $get_base_amount_or_hours . " (" . number_format($result, 2) . " day(s))";
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
                        'total_net_pay' =>$this->get_netPay($this->wages,$this->hours_worked,$this->net_pay),
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

    public function get_yearly_data($key)
    {
            $working_hours_per_day = auth()->user()->company?->working_hours_per_day;
            $payslip_year = date('Y', strtotime($this->first_date));
            $payslip_month = date('m', strtotime($this->first_date));
            $payslip_data = Payslip::query()
                                        ->whereBetween('first_date', [
                                            date("$payslip_year-1-1"), // Start of the year
                                            date("$payslip_year-$payslip_month-31"), // End of the year
                                        ])
                                        ->whereBetween('last_date', [
                                            date("$payslip_year-1-1"), // Start of the year
                                            date("$payslip_year-$payslip_month-31"), // End of the year
                                        ])
                                        ->where('employee_id', $this->employee->id)
                                        ->orderBy('created_at', 'ASC')
                                        ->get();
            $data['hours'] = 0;
            $data['overtime_hours'] = 0;
            $data['total_fixed_pay'] = 0;
            $data['taxable_allowances'] = 0;
            $data['non_taxable_allowances'] = 0;
            $data['total_gross_pay'] = 0;
            $data['taxable_gross_pay'] = 0;
            $data['ytd_tax_paid'] = 0;
            $data['tax_amount'] = 0;
            $data['total_staff_contribution'] = 0;
            $data['total_company_contribution'] = 0;
            $data['total_staff_cost'] = 0;
            $data['total_net_pay'] = 0;
            foreach($payslip_data as $value)
            {
                if($key == "hours"){
                    $data['hours'] += $value->hours_worked;
                }
                if($key == "total_fixed_pay"){
                    $data["total_fixed_pay"] += ($value->wages + $value->additional_pay) - $value->leave_deduction;
                }
                if($key == "taxable_allowances"){
                    $data["taxable_allowances"] += $value->taxable_allowance;
                }
                if($key == "non_taxable_allowances"){
                    $data["non_taxable_allowances"] += $value->non_taxable_allowance;
                }
                if($key == "total_gross_pay"){
                    $data["total_gross_pay"] += (($value->wages + $value->additional_pay) - $value->leave_deduction) + $value->taxable_allowance + $value->non_taxable_allowance;
                }
                if($key == "taxable_gross_pay"){
                    $data["taxable_gross_pay"] += (($value->wages + $value->additional_pay) - $value->leave_deduction) + $value->taxable_allowance + $value->non_taxable_allowance - $value->non_taxable_allowance;
                }
                if($key == "ytd_tax_paid"){
                    $data["ytd_tax_paid"] += $value->tax_value + $value->post_tax_value;
                }
                if($key == "tax_amount"){
                    $data["tax_amount"] += $value->tax_value + $value->post_tax_value;
                }
                if($key == "total_staff_contribution"){
                    $data["total_staff_contribution"] += $value->total_employee_deduction;
                }
                if($key == "total_company_contribution"){
                    $data["total_company_contribution"] += $value->company_contribution;
                }
                if($key == "total_staff_cost"){
                    // $data["total_staff_cost"] += ($value->gross_pay_before_tax + $value->company_contribution);
                    $data["total_staff_cost"] += (($this->wages + $this->additional_pay) - $this->leave_deduction) + $this->taxable_allowance + $this->non_taxable_allowance + $value->company_contribution;
                }
                if($key == "total_net_pay"){
                    $data["total_net_pay"] += $value->net_pay;
                }
            }
            return $data;
    }
}
