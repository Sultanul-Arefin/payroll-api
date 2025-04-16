<?php

namespace Modules\Payslip\Http\Resources;

use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use JsonSerializable;
use Modules\Payslip\Entities\Payslip;
use Modules\Payslip\Entities\PayslipDetailsForDeduction;
use Carbon\Carbon;
use Modules\LeaveManagement\Entities\UserLeave;
use Modules\LeaveManagement\Entities\UserLeaveDetail;
use Modules\SalaryItemsName\Entities\LeaveSalaryItems;
use Modules\Payslip\Entities\PayslipDetail;


class ViewUKPayslipResource extends JsonResource
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
            'items_details' => $this->get_payslip_details($this->payslip_details),
            //'deduction_details' => $this->get_deduction_details(),
            'payment_date' => $this->payment_date,
            'wage_deduction' => $this->leave_deduction,
            'total_fixed_pay' => $this->wages - $this->leave_deduction,
            'fixed_pay_details' => $this->wages,
            'additional_pay' =>  $this->additional_pay, // additional pay goes here
            //'wage_deduction' => $this->taxable_allowance,
            'total_fixed_pay' => ($this->wages + $this->additional_pay) - $this->leave_deduction,
            'taxable_allowance' => $this->taxable_allowance,
            'non_taxable_allowance' => $this->non_taxable_allowance,
            //'total_gross_pay' => $this->gross_pay_before_tax,
            'total_gross_pay' => round(floatval((($this->wages + $this->additional_pay) - $this->leave_deduction) + $this->taxable_allowance + $this->non_taxable_allowance),2), // this is accurate            'tax_amount' => $this->tax_value + $this->post_tax_value,
            'taxable_gross_pay' => round(floatval(((($this->wages + $this->additional_pay) - $this->leave_deduction) + $this->taxable_allowance + $this->non_taxable_allowance) - $this->non_taxable_allowance),2), // this is accurate // total_gross_pay - non_taxable_allowance            'pay_due_before_deduction' => $this->pay_due_before_deduction,
            //'staff_social_charges' => 0.00,
            'tax_amount' =>round(floatval($this->tax_value + $this->post_tax_value),2),
            'gross_pay_after_tax' => $this->gross_pay_after_tax,
            'pay_due_before_deduction' => $this->pay_due_before_deduction,
            'social_deduction' => $this->get_social_deduction(),
            'other_deduction' => $this->get_other_deduction(),
            'total_deductions' => $this->total_employee_deduction,
            'total_deductions_all' => $this->get_total_company_deduction(),
            'staff_social_charges' => 0.00,
            'total_net_pay' => $this->net_pay,
           // 'overall_calculation' => $this->overall_calculation(),
            'year_to_date' => $this->getYearToDateCalculations(),
            'annual_leave' => $this->get_annual_leave_calculation($this->employee),

        ];
    }

    public function get_annual_leave_calculation($employee)
    {
        return [
            'annual_leave_quota' => $this->getAnnualLeaveQuota(),
            'annual_leave_taken' => $this->getAnnualLeaveTaken($this->employee->id),
            // 'remaining_annual_leave' => $this->getAnnualLeaveQuota() - $this->getAnnualLeaveTaken($this->employee->id)
            'remaining_annual_leave' => $this->getAnnualLeaveQuota() - $this->getTotalAnnualLeaveTaken($this->employee->id),
            'sick_leave_quota' => $this->getSickLeaveQuota(),
            'sick_leave_taken' => $this->getSickLeaveTaken($this->employee->id),
            // 'remaining_annual_leave' => $this->getAnnualLeaveQuota() - $this->getAnnualLeaveTaken($this->employee->id)
            'remaining_sick_leave' => $this->getSickLeaveQuota() - $this->getTotalSickLeaveTaken($this->employee->id),
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



    // public function get_deduction_details()
    // {
    //     $deduction_details = PayslipDetailsForDeduction::query()
    //                     ->where('payslip_id', $this->id)
    //                     ->get();
    //     $deduction_value = 0;
    //     foreach($deduction_details as $dv)
    //     {
    //         $dv->pay_details = $dv->salary_item_name?->name;
    //         $dv->title = $dv->title;
    //         $dv->employee_amount = $dv->employee_amount;
    //         $dv->government_or_company_amount = $dv->government_or_company_amount;

    //         // deduction_calculation
    //         $deduction_value += $dv->employee_amount;

    //         unset($dv->payslip_id, $dv->salary_item_id, $dv->created_at, $dv->updated_at, $dv->pay_details, $dv->salary_item_name, $dv->id);
    //     }

    //     return [
    //         //'title' => $value?->salary_item_name?->name,
    //         'deduction_details' => $deduction_details,
    //         'total_deduction_value' => $deduction_value
    //     ];
    // }

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
        $total_employee_amount = 0;


        foreach($deduction_details as $value)
        {

            $total_company_amount += $value->government_or_company_amount;
            $total_employee_amount += $value->employee_amount;


            array_push($response, [
                'title' => $value?->salary_item_name?->name,
                'base' => $this->gross_pay_before_tax,
                'employee_rate' => $value->employee_amount_rate,
                'employee_amount' => round(floatval($value->employee_amount),2),
                'company_rate' => $value->government_or_company_amount_rate,
                'company_amount' => round(floatval($value->government_or_company_amount),2),
            ]);
        }
        return [
            'deductions' => $response,
            'total_company_amount' => round(floatval($total_company_amount),2),
            'total_employee_amount' =>round(floatval($total_employee_amount),2),

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
                $total_employee_amount = 0;


                foreach($deduction_details as $value)
                {
                    $total_company_amount += $value->government_or_company_amount;
                    $total_employee_amount += $value->employee_amount;


                    array_push($response, [
                        'title' => $value?->salary_item_name?->name,
                        'base' => $this->gross_pay_before_tax,
                        'employee_rate' => $value->employee_amount_rate,
                        'employee_amount' => round(floatval($value->employee_amount),2),
                        'company_rate' => $value->government_or_company_amount_rate,
                        'company_amount' => round(floatval($value->government_or_company_amount),2),
                    ]);
                }
                return [
                    'deductions' => $response,
                    'total_company_amount' => round(floatval($total_company_amount),2),
                    'total_employee_amount' =>round(floatval($total_employee_amount),2),
                ];
    }

    public function get_total_company_deduction()
            {
                $other_deduction = $this->get_other_deduction();
                $social_deduction = $this->get_social_deduction();

                $total_company_amount = $other_deduction['total_company_amount'] + $social_deduction['total_company_amount'];
                $total_employee_amount = $other_deduction['total_employee_amount'] + $social_deduction['total_employee_amount'];

                $total_company_amount = round(floatval($total_company_amount),2);
                $total_employee_amount = round(floatval($total_employee_amount),2);
                return [
                    'total_company_amount' => $total_company_amount,
                    'total_employee_amount' => $total_employee_amount,
                ];
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

    //         // payslip_calculation
    //         $payslip_value += $payslip_detail->amount;
    //         // unset these keys from the response
    //         unset($payslip_detail->salary_item, $payslip_detail->id, $payslip_detail->created_at, $payslip_detail->updated_at, $payslip_detail->payslip_id, $payslip_detail->salary_item_id);
    //     }
    //     return [
    //         'payslip_details' => $payslip_details,
    //         'total_payslip_value' => $payslip_value
    //     ];
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
            'total_payslip_value' => $payslip_value
        ];
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

    // public function getYearToDateCalculations()
    //     {
    //         $startOfYear = now()->startOfYear();
    //         $yearToDatePayslips = Payslip::where('employee_id', $this->employee->id)
    //                             ->whereBetween('payment_date', [$startOfYear, $this->payment_date])
    //                             ->get();

    //         $yearToDateGrossPay = $yearToDatePayslips->sum('total_gross_pay');

    //         $yearToDateTaxableGross =$yearToDatePayslips->sum($this->wages - $this->leave_deduction + $this->taxable_allowance);
    //         $tax= $yearToDatePayslips->sum($this->tax_value + $this->post_tax_value);
    //        // $yearToDateNetPay =  $yearToDateTaxableGross - $yearToDateTotalDeductions;

    //         return [
    //             'gross_pay' => $yearToDateGrossPay,
    //             'taxable_gross' => $yearToDateTaxableGross,
    //             //'total_deductions' => $yearToDateTotalDeductions,
    //             'tex' => $tax,
    //         ];
    //     }

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



public function getYearToDateCalculations()
{
    // payment_date starting year
    $paymentDate = Carbon::parse($this->payment_date);
    $startOfYear = $paymentDate->copy()->startOfYear()->toDateString();

    // // Devaging
    // \Log::info('Start of Year: ' . $startOfYear);
    // \Log::info('Payment Date: ' . $paymentDate->toDateString());
    // \Log::info('Employee ID: ' . $this->employee->id);

    // payslip Filter
    $yearToDatePayslips = Payslip::where('employee_id', $this->employee->id)
        ->whereBetween('payment_date', [$startOfYear, $paymentDate->toDateString()])
        ->get();

    if ($yearToDatePayslips->isEmpty()) {
        \Log::warning('No Payslips found in range.', [
            'employee_id' => $this->employee->id,
            'start_of_year' => $startOfYear,
            'payment_date' => $paymentDate->toDateString(),
        ]);
        return [
            'gross_pay' => 0,
            'taxable_gross' => 0,
            'tax' => 0,
        ];
    }

    // Yearly Gross Pay
    $yearToDateGrossPay = $yearToDatePayslips->sum(function ($payslip) {
        return (
            ($payslip->wages + $payslip->additional_pay - $payslip->leave_deduction)
            + $payslip->taxable_allowance
            + $payslip->non_taxable_allowance
        );
    });

    // Yearly Taxable Gross Pay
    $yearToDateTaxableGross = $yearToDatePayslips->sum(function ($payslip) {
        return (
            ($payslip->wages + $payslip->additional_pay - $payslip->leave_deduction)
            + $payslip->taxable_allowance
        );
    });

    // Yearly Net Pay
    $yearToDateTaxPay = $yearToDatePayslips->sum(function ($payslip) {
        return $payslip->tax_value + $payslip->post_tax_value;
    });

   $yearToDateTaxPay = round(floatval($yearToDateTaxPay),2);

    return [
        'gross_pay' => round(floatval($yearToDateGrossPay),2),
        'taxable_gross' => round(floatval($yearToDateTaxableGross),2),
        'tax' => $yearToDateTaxPay,
    ];
}


    public function overall_calculation()
    {
        return [
            'monthly' => [
                [
                    'hours' => 148,
                    'overtime_hours' => 0,
                    'total_fixed_pay' => 8400,
                    'taxable_allowances' => 12000,
                    'non_taxable_allowances' => 9500,
                    'total_gross_pay' => 30000,
                    'taxable_gross_pay' => 20400,
                    'ytd_tax_paid' => 0,
                    'total_staff_contribution' => 0,
                    'total_company_contribution' => 0,
                    'total_staff_cost' => 30000,
                    'total_net_pay' => 30000,
                ],
            ],
            'yearly' => [
                [
                    'hours' => 148,
                    'overtime_hours' => 0,
                    'total_fixed_pay' => 8400,
                    'taxable_allowances' => 12000,
                    'non_taxable_allowances' => 9500,
                    'total_gross_pay' => 30000,
                    'taxable_gross_pay' => 20400,
                    'ytd_tax_paid' => 0,
                    'total_staff_contribution' => 0,
                    'total_company_contribution' => 0,
                    'total_staff_cost' => 30000,
                    'total_net_pay' => 30000,
                ],
            ],
            'total_net_pay' => $this->net_pay,
        ];
    }
}
