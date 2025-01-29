<?php

namespace Modules\Payslip\Http\Resources;

use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use JsonSerializable;
use Modules\LeaveManagement\Entities\UserLeave;
use Modules\LeaveManagement\Entities\UserLeaveDetail;
use Modules\Payslip\Entities\Payslip;
use Modules\Payslip\Entities\PayslipDetailsForDeduction;
use Modules\SalaryItemsName\Entities\LeaveSalaryItems;
use Modules\Payslip\Entities\PayslipDetail;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;


class ViewUSAPayslipResource extends JsonResource
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
            'deduction_details' => $this->get_deduction_details(),
            'total_deductions' => $this->calculateDeductionsAndTax(),
            'employee_id' =>$this->employee_id,
            'payment_date' => $this->payment_date,
            'fixed_pay_details' => $this->wages,
            'additional_pay' => $this->additional_pay,
            'wage_deduction' => $this->leave_deduction,
            'total_fixed_pay' => ($this->wages + $this->additional_pay) - $this->leave_deduction,
            'taxable_allowance' => $this->taxable_allowance,
            'non_taxable_allowance' => $this->non_taxable_allowance,
           // 'total_gross_pay' => $this->pay_due_before_deduction,
            //'taxable_gross_pay' => $this->gross_pay_before_tax,
            'total_gross_pay' => (($this->wages + $this->additional_pay) - $this->leave_deduction) + $this->taxable_allowance + $this->non_taxable_allowance, // this is accurate            'tax_amount' => $this->tax_value + $this->post_tax_value,
            'taxable_gross_pay' => ((($this->wages + $this->additional_pay) - $this->leave_deduction) + $this->taxable_allowance + $this->non_taxable_allowance) - $this->non_taxable_allowance, // this is accurate // total_gross_pay - non_taxable_allowance            'pay_due_before_deduction' => $this->pay_due_before_deduction,
            //'staff_social_charges' => 0.00,
            'staff_social_charges' => $this->get_staff_social_charges(), // staff social charge goes here
            'other_deduction_summary' => $this->get_other_deduction(),
            'income_tax' => $this->getIncomeTaxAndCategoryDetails($this->payslip_details),
            'total_deduction' => $this->total_employee_deduction,
            'total_company_deduction' => $this->get_total_company_deduction(),
            //'taxable_gross_pay' => $this->calculate_taxable_gross_pay(),
            //'overall_calculation' => $this->overall_calculation(),
            'total_net_pay' => $this->net_pay,
            'summary' => [
                'year_to_date' => $this->getYearToDateCalculations(),
            ],
            'annual_leave' => $this->get_annual_leave_calculation($this->employee)
        ];	
    }

    

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
    
        

        public function get_deduction_details()
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
                $social_deduction = $this->get_deduction_details();

                $total_company_amount = $other_deduction['total_company_amount'] + $social_deduction['total_company_amount'];
                $total_employee_amount = $other_deduction['total_employee_amount'] + $social_deduction['total_employee_amount'];

                return [
                    'total_company_amount' => $total_company_amount,
                    'total_employee_amount' => $total_employee_amount,
                ];
            }

            public function calculateDeductionsAndTax()
                {
                    $deductionDetails = $this->get_total_company_deduction();
                    $incomeTaxDetails = $this->getIncomeTaxAndCategoryDetails($this->payslip_details);

                    return [
                        'total_deductions' => $deductionDetails['total_employee_amount'] ?? 0,
                        'total_income_tax' => $incomeTaxDetails['total_amount'] ?? 0,
                        'combined_total' => 
                            ($deductionDetails['total_employee_amount'] ?? 0) + 
                            ($incomeTaxDetails['total_amount'] ?? 0),
                    ];
                }

        

        public function get_staff_social_charges()
                {
                    return PayslipDetailsForDeduction::query()
                            ->where('payslip_id', $this->id)
                            ->sum('employee_amount');
                }

        private function getIncomeTaxAndCategoryDetails($payslipDetails)
       
            {
                //return $payslipDetails;
                $filteredDetails = [];
                $totalAmount = 0;

                foreach ($payslipDetails as $detail) {
                    if (in_array($detail['category_id'], [5, 6])) { // Check if category_id is 5 or 6
                        $filteredDetails[] = [
                            'pay_details' => $detail['pay_details'],
                            'base_amount_or_hours' => $detail['base_amount_or_hours'],
                            'rate' => $detail['rate'],
                            'amount' => $detail['amount'],
                        ];
                        $totalAmount += $detail['amount']; // Accumulate the total rate
                    }
                }

                return [
                    'filtered_details' => $filteredDetails,
                    'total_amount' => $totalAmount,
                    'total_deductions_tax'=>$totalAmount + $this->total_employee_deduction,
                ];
            }

            


        

        public function get_payslip_details($payslip_details)
        {
            $payslip_value = 0;
            $filteredDetails = [];
        
            $filtered_details = $payslip_details->filter(function ($payslip_detail) {
                return !in_array($payslip_detail->salary_item->name, ['Annual Leave', 'Sick Leave']);
            });
    
            foreach($filtered_details as $payslip_detail){
                // if($payslip_detail->salary_item->name == "Annual Leave" || $payslip_detail->salary_item->name == "Annual Leave"){
    
                // } else{
                $payslip_detail->pay_details = $payslip_detail->salary_item->name ?? 'N/A';
                $payslip_detail->base_amount_or_hours = $payslip_detail->base_amount_or_hours ?? 0;
                $payslip_detail->rate = $payslip_detail->rate ?? 0;        
                $payslip_detail->amount = $payslip_detail->amount ?? 0;
                if (in_array($payslip_detail->salary_item->name, ["Bonus", "Overtime Rate", "Double Overtime Rate"])) {
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
                unset($payslip_detail->salary_item, $payslip_detail->id, $payslip_detail->created_at, $payslip_detail->updated_at, $payslip_detail->payslip_id, $payslip_detail->salary_item_id);

                // unset(
                //     $payslip_detail->salary_item,
                //     $payslip_detail->id,
                //     $payslip_detail->amount,
                //     $payslip_detail->created_at,
                //     $payslip_detail->updated_at,
                //     $payslip_detail->payslip_id,
                //     $payslip_detail->salary_item_id
                // );
        
                $filteredDetails[] = $payslip_detail;
            }
        
            return [
                'payslip_details' => $filteredDetails,
                'total_payslip_value_employee' => $payslip_value,
            ];
        }

        // public function get_payslip_details($payslip_details)
        //     {
        //         // Filter out rows where salary_item->name is "Annual Leave" or "Sick Leave"
        //         $filtered_details = $payslip_details->reject(function ($payslip_detail) {
        //             return in_array($payslip_detail->salary_item->name, ['Annual Leave', 'Sick Leave']);
        //         });

        //         $total_payslip_value = 0;

        //         $filtered_details = $filtered_details->reject(function ($payslip_detail) {
        //             if (in_array(optional($payslip_detail->salary_item->salaryItemsCategory)->id, [5, 6])) {
        //                 return true;
        //             }
        //             return false;
        //         });

        //         $filtered_details->transform(function ($payslip_detail) use (&$total_payslip_value) {
        //             $payslip_detail->pay_details = $payslip_detail->salary_item->name ?? 'N/A';
        //             $payslip_detail->base_amount_or_hours = $payslip_detail->base_amount_or_hours ?? 0;
        //             $payslip_detail->rate = $payslip_detail->rate ?? 0;
        //             $payslip_detail->amount = $payslip_detail->amount ?? 0;
                    
        //             if (in_array($payslip_detail->salary_item->name, ["Bonus", "Overtime Rate", "Double Overtime Rate"])) {
        //                 $payslip_detail->category_id = optional($payslip_detail->salary_item->salaryItemsCategory)->id . "_additional";
        //             } else {
        //                 $payslip_detail->category_id = optional($payslip_detail->salary_item->salaryItemsCategory)->id;
        //             }
                    

        //             // Accumulate total payslip value
        //             $total_payslip_value += $payslip_detail->amount;

        //             // Unset unnecessary keys
        //             unset(
        //                 $payslip_detail->salary_item,
        //                 $payslip_detail->id,
        //                 $payslip_detail->created_at,
        //                 $payslip_detail->updated_at,
        //                 $payslip_detail->payslip_id,
        //                 $payslip_detail->salary_item_id
        //             );
                    
        //             return $payslip_detail;
        //         });

        //         return [
        //             'payslip_details' => $filtered_details->values(),
        //             'total_payslip_value_employee' => $total_payslip_value,
        //         ];
        //     }


        

       

//        public function getYearToDateCalculations()
// {
//     $startOfYear = now()->startOfYear();

//     $yearToDatePayslips = Payslip::where('employee_id', $this->employee->id)
//         ->whereBetween('payment_date', [$startOfYear, $this->payment_date])
//         ->get();

//     // Calculate yearly gross pay
//     $yearToDateGrossPay = $yearToDatePayslips->sum(function ($payslip) {
//         return (($payslip->wages + $payslip->taxable_allowance) - $payslip->leave_deduction)
//             + $payslip->taxable_allowance + $payslip->non_taxable_allowance;
//     });

//     // Calculate yearly taxable gross pay
//     $yearToDateTaxableGross = $yearToDatePayslips->sum(function ($payslip) {
//         return (($payslip->wages + $payslip->taxable_allowance) - $payslip->leave_deduction)
//             + $payslip->taxable_allowance;
//     });

//     // Calculate yearly net pay
//     $yearToDateNetPay = $yearToDatePayslips->sum(function ($payslip) {
//         return $payslip->net_pay;
//     });
//     // Return all calculations as an array
//     return [
//         'gross_pay' => $yearToDateGrossPay,
//         'taxable_gross' => $yearToDateTaxableGross,
//         'net_pay' => $yearToDateNetPay,
//     ];
// }
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
            'net_pay' => 0,
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
    $yearToDateNetPay = $yearToDatePayslips->sum(function ($payslip) {
        return $payslip->net_pay;
    });

    return [
        'gross_pay' => $yearToDateGrossPay,
        'taxable_gross' => $yearToDateTaxableGross,
        'net_pay' => $yearToDateNetPay,
    ];
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
                    'total_gross_pay' => $this->pay_due_before_deduction,
                    //'taxable_gross_pay' => $this->gross_pay_before_tax - $this->non_taxable_allowance,
                    'taxable_gross_pay' => $this->calculate_taxable_gross_pay(),
                    'ytd_tax_paid' => 0,
                    'total_staff_contribution' => $this->total_employee_deduction,
                    'total_company_contribution' => $this->company_contribution,
                    'total_staff_cost' =>  $this->gross_pay_before_tax,
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
                    'total_gross_pay' => $this->pay_due_before_deduction,
                   // 'taxable_gross_pay' => $this->gross_pay_before_tax - $this->non_taxable_allowance,
                   'taxable_gross_pay' => $this->calculate_taxable_gross_pay(),
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