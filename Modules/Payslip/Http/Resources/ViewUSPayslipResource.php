<?php

namespace Modules\Payslip\Http\Resources;

use Carbon\Carbon; 
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use JsonSerializable;
use Modules\LeaveManagement\Entities\UserLeave;
use Modules\LeaveManagement\Entities\UserLeaveDetail;
use Modules\Payslip\Entities\Payslip;
use Modules\Payslip\Entities\PayslipDetail;
use Modules\Payslip\Entities\PayslipDetailsForDeduction;
use Modules\SalaryItemsName\Entities\LeaveSalaryItems;

class ViewUSPayslipResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array<string, mixed>
     */
     public function toArray($request)
    {
        return [
            'items_details' => $this->get_payslip_details($this->payslip_details),
            'total_cash_deduction' => $this->calculateDeductionsAndTax(),
            'payment_date' => $this->payment_date, //date('Y-m-d H:i:s')
            'fixed_pay_details' => round(floatval($this->wages),2),
            'additional_pay' => $this->additional_pay, // additional pay goes here
            'wage_deduction' => $this->leave_deduction,
            'total_fixed_pay' => ($this->wages + $this->additional_pay) - $this->leave_deduction,
            'taxable_allowance' => $this->taxable_allowance,
            'non_taxable_allowance' => $this->non_taxable_allowance,
            'total_gross_pay' => round(floatval((($this->wages + $this->additional_pay) - $this->leave_deduction) + $this->taxable_allowance + $this->non_taxable_allowance),2), // this is accurate
            // 'total_gross_pay' => $this->gross_pay_before_tax,
            // 'taxable_gross_pay' => $this->gross_pay_before_tax, // this is added due to question from Nabila. Have to check the calculation again
            'taxable_gross_pay' =>round(((($this->wages + $this->additional_pay) - $this->leave_deduction) + $this->taxable_allowance + $this->non_taxable_allowance) - $this->non_taxable_allowance,2), // this is accurate // total_gross_pay - non_taxable_allowance
            // 'taxable_gross_pay' => $this->gross_pay_before_tax - $this->non_taxable_allowance,
            //'tax_amount' => $this->tax_value + $this->post_tax_value,
            'tax_amount' => round($this->tax_value + $this->post_tax_value, 2),
            'gross_pay_after_tax' => $this->gross_pay_after_tax,
            'pay_due_before_deduction' => $this->pay_due_before_deduction,
            'staff_social_charges' => $this->get_staff_social_charges(), // staff social charge goes here
           // 'total_net_pay' => round(floatval($this->net_pay),2),
            'total_net_pay' =>$this->get_netPay($this->wages,$this->hours_worked,$this->net_pay),
            'income_tax' => $this->getIncomeTaxAndCategoryDetails($this->payslip_details),
            'other_deduction_summary' => $this->get_other_deduction(),
            'deduction_details' => $this->get_deduction_details(),
            'total_social_deduction' => $this->get_total_social_deduction(),
            'total_other_deduction' => $this->get_total_other_deduction(),
            //'total_deduction' => $this->total_employee_deduction,
            'total_company_deduction' => $this->get_total_company_deduction(),
            'annual_leave' => $this->get_annual_leave_calculation($this->employee),
            'overall_calculation' => $this->overall_calculation(),   
            'year_to_date' => $this->getYearToDateCalculations(),         
        ];
    }

    public function get_netPay($wages,$hours_worked,$net_pay){
        if ($wages<=0 && $hours_worked<=0 ){
            return 0;
        }else {
            return round(floatval($net_pay),2);
        }
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


    public function get_deduction_details()
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
            $social_deduction = $this->get_deduction_details();

            $total_company_amount = $other_deduction['total_company_amount'] + $social_deduction['total_company_amount'];
            $total_employee_amount = $other_deduction['total_employee_amount'] + $social_deduction['total_employee_amount'];
            $yearly_total_company_amount = $other_deduction['yearly_total_company_amount'] + $social_deduction['yearly_total_company_amount'];
            $yearly_total_employee_amount = $other_deduction['yearly_total_employee_amount'] + $social_deduction['yearly_total_employee_amount'];

            return [
                'total_company_amount' => $total_company_amount,
                'total_employee_amount' => $total_employee_amount,
                'yearly_total_company_amount' => $yearly_total_company_amount,
                'yearly_total_employee_amount' => $yearly_total_employee_amount,
            ];
    }

    public function calculateDeductionsAndTax()
    {
            $deductionDetails = $this->get_total_company_deduction();
            $incomeTaxDetails = $this->getIncomeTaxAndCategoryDetails($this->payslip_details);

            return [
                'deductionsForMonth' => $deductionDetails['total_employee_amount'] ?? 0,
                'total_deductions_year_withOutTex' => $deductionDetails['yearly_total_employee_amount'] ?? 0,
                'income_tax_deductions_month' => $incomeTaxDetails['total_amount'] ?? 0,
                'total_income_tax_year' => $incomeTaxDetails['yearly_total'] ?? 0,
                'total_deductions' =>
                    ($deductionDetails['total_employee_amount'] ?? 0) +
                    ($incomeTaxDetails['total_amount'] ?? 0),
                'cumulative' =>
                    ($deductionDetails['yearly_total_employee_amount'] ?? 0) +
                    ($incomeTaxDetails['yearly_total'] ?? 0),
                        
                ];
    }


    // private function getIncomeTaxAndCategoryDetails($payslipDetails)
    // {
    //     $filteredDetails = [];
    //     $totalAmount = 0;

        
    //     if (empty($payslipDetails)) {
    //         return [
    //             'filtered_details' => [],
    //             'total_amount' => 0,
    //             'total_deductions_tax' => $this->total_employee_deduction,
    //         ];
    //     }

   
    //     $current_month = date('m', strtotime($payslipDetails[0]['first_date'] ?? now()));

    //      $yearly_payslip_data = $this->get_yearly_payslip_totals($this->employee_id, $current_month);

    //     foreach ($payslipDetails as $detail) {
    //             if (in_array($detail['category_id'], [5, 6])) { // Check if category_id is 5 or 6
    //              $pay_detail_name = $detail['pay_details'] ?? 'N/A';

    //              $yearly_total = $yearly_payslip_data[$pay_detail_name] ?? 0;

    //             $filteredDetails[] = [
    //                 'pay_details' => $pay_detail_name,
    //                 'base_amount_or_hours' => $detail['base_amount_or_hours'],
    //                 'rate' => $detail['rate'],
    //                 'amount' => $detail['amount'],
    //                 'yearly_total' => $yearly_total, 
    //             ];

    //             $totalAmount += $detail['amount']; // Accumulate the total amount
    //         }
    //     }

    //     return [
    //         'filtered_details' => $filteredDetails,
    //         'total_amount' => $totalAmount,
    //         'total_deductions_tax' => $totalAmount + $this->total_employee_deduction,
    //     ];
    // }


// private function getIncomeTaxAndCategoryDetails($payslipDetails)
// {
//     $filteredDetails = [];
//     $totalAmount = 0;
//     $total_amount_yearly = 0;

//     if (empty($payslipDetails)) {
//         return [
//             'filtered_details' => [],
//             'total_amount' => 0,
//             'total_deductions_tax' => $this->total_employee_deduction,
//         ];
//     }

//     $current_month = date('m', strtotime($payslipDetails[0]['first_date'] ?? now()));
//     $yearly_payslip_data = $this->get_yearly_payslip_totals_tax($this->employee_id, $current_month);

//     foreach ($payslipDetails as $detail) {
//         if (in_array($detail['category_id'], [5, 6])) {
//             $pay_detail_name = trim(preg_replace('/\s+/', ' ', $detail['pay_details'] ?? 'N/A'));

//             // Default yearly_total
//             $yearly_total = 0;

//             // If it's a Threshold, multiply monthly amount * 12
//             if (strpos($pay_detail_name, 'Threshold') !== false) {
//                 $yearly_total = $detail['amount'] * 12;
//             } else {
//                 // Try exact match first
//                 if (isset($yearly_payslip_data[$pay_detail_name])) {
//                     $yearly_total = $yearly_payslip_data[$pay_detail_name];
//                 } else {
//                     // Try loose match if exact doesn't work
//                     foreach ($yearly_payslip_data as $key => $value) {
//                         if (stripos(trim($key), $pay_detail_name) !== false || stripos($pay_detail_name, trim($key)) !== false) {
//                             $yearly_total = $value;
//                             break;
//                         }
//                     }
//                 }
//             }

//             // Fallback custom value for Threshold
//             if ($yearly_total === 0 && strpos($pay_detail_name, 'Threshold') !== false) {
//                 $yearly_total = 1000;
//             }

//             $filteredDetails[] = [
//                 'pay_details' => $pay_detail_name,
//                 'base_amount_or_hours' => $detail['base_amount_or_hours'],
//                 'rate' => $detail['rate'],
//                 'amount' => $detail['amount'],
//                 'yearly_total' => $yearly_total,
//             ];

//             $totalAmount += $detail['amount'];
//             $total_amount_yearly += $yearly_total;
//         }
//     }

//     return [
//         'filtered_details' => $filteredDetails,
//         'total_amount' => round(floatval($totalAmount), 2),
//         'total_amount_yearly' => round(floatval($total_amount_yearly), 2),
//     ];
// }

private function getIncomeTaxAndCategoryDetails($payslipDetails)
{
    $filteredDetails = [];
    $totalAmount = 0;
    $total_amount_yearly = 0;

    if (empty($payslipDetails)) {
        return [
            'filtered_details' => [],
            'total_amount' => 0,
            'total_amount_yearly' => 0,
        ];
    }

    $current_month = date('m', strtotime($payslipDetails[0]['first_date'] ?? now()));
    $yearly_payslip_data = $this->get_yearly_payslip_totals_tax($this->employee_id, $current_month);

    foreach ($payslipDetails as $detail) {
        // Only process tax-related categories (5 and 6)
        if (!in_array($detail['category_id'], [5, 6])) {
            continue;
        }

        $pay_detail_name = trim(preg_replace('/\s+/', ' ', $detail['pay_details'] ?? 'N/A'));

        // Default yearly_total = 0
        $yearly_total = 0;

        // Check for exact match first
        if (isset($yearly_payslip_data[$pay_detail_name])) {
            $yearly_total = $yearly_payslip_data[$pay_detail_name];
        } else {
            // Try partial/loose match
            foreach ($yearly_payslip_data as $key => $value) {
                $normalized_key = strtolower(trim($key));
                $normalized_name = strtolower(trim($pay_detail_name));

                if (str_contains($normalized_key, $normalized_name) || str_contains($normalized_name, $normalized_key)) {
                    $yearly_total = $value;
                    break;
                }
            }
        }

        // Prepare filtered detail
        $filteredDetails[] = [
            'pay_details' => $pay_detail_name,
            'base_amount_or_hours' => $detail['base_amount_or_hours'],
            'rate' => $detail['rate'],
            'amount' => round(floatval($detail['amount']), 2),
            'yearly_total' => round(floatval($yearly_total), 2),
        ];

        $totalAmount += floatval($detail['amount']);
        $total_amount_yearly += floatval($yearly_total);
    }

    return [
        'filtered_details' => $filteredDetails,
        'total_amount' => round($totalAmount, 2),
        'total_amount_yearly' => round($total_amount_yearly, 2),
    ];
}


    public function get_total_other_deduction()
    {
        $deduction_details = PayslipDetailsForDeduction::query()
                            ->whereHas(
                                'salary_item_name', function(Builder $builder){
                                    $builder->where('salary_items_category_id', 8);
                                }
                            )
                            ->where('payslip_id', $this->id)
                            ->get();
        $total = 0;
        foreach($deduction_details as $value)
        {
            $total += $value->employee_amount + $value->government_or_company_amount;
            // array_push($response, [
            //     'title' => $value?->salary_item_name?->name,
            //     'base' => $this->gross_pay_before_tax,
            //     'employee_rate' => $value->employee_amount_rate,
            //     'employee_amount' => $value->employee_amount,
            //     'company_rate' => $value->government_or_company_amount_rate,
            //     'company_amount' => $value->government_or_company_amount
            // ]);
        }
        return $total;
    }

    public function get_total_social_deduction()
        {
            $deduction_details = PayslipDetailsForDeduction::query()
                                ->whereHas(
                                    'salary_item_name', function(Builder $builder){
                                        $builder->where('salary_items_category_id', 7);
                                    }
                                )
                                ->where('payslip_id', $this->id)
                                ->get();
            $total = 0;
            foreach($deduction_details as $value)
            {
                $total += $value->employee_amount + $value->government_or_company_amount;
                // array_push($response, [
                //     'title' => $value?->salary_item_name?->name,
                //     'base' => $this->gross_pay_before_tax,
                //     'employee_rate' => $value->employee_amount_rate,
                //     'employee_amount' => $value->employee_amount,
                //     'company_rate' => $value->government_or_company_amount_rate,
                //     'company_amount' => $value->government_or_company_amount
                // ]);
            }
            return $total;
        }

    public function get_staff_social_charges()
        {
            return PayslipDetailsForDeduction::query()
                    ->where('payslip_id', $this->id)
                    ->sum('employee_amount');
        }

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
        
    // public function get_payslip_details($payslip_details)
    // {
    //     // Check if the collection is empty
    //     if ($payslip_details->isEmpty()) {
    //         return collect([]); // Return an empty collection instead of throwing an error
    //     }

    //     // Get the current month from the first payslip's first_date
    //     $current_month = date('m', strtotime($payslip_details[0]->payslip->first_date)); 

    //     // Get yearly totals up to the current month
    //     $yearly_payslip_data = $this->get_yearly_payslip_totals($this->employee_id, $current_month);

    //     // Filter out rows where salary_item->name is "Annual Leave" or "Sick Leave"
    //     $filtered_details = $payslip_details->filter(function ($payslip_detail) {
    //         return !in_array($payslip_detail->salary_item->name, ['Annual Leave', 'Sick Leave']);
    //     });

    //     // Loop through each filtered payslip detail and add yearly total
    //     foreach ($filtered_details as $payslip_detail) {
    //         $pay_detail_name = $payslip_detail->salary_item->name;

    //         // Set the base_amount_or_hours, rate, and amount
    //         $payslip_detail->pay_details = $pay_detail_name;
    //         $payslip_detail->base_amount_or_hours = $this->get_base_amount_or_hours($payslip_detail->base_amount_or_hours);
    //         $payslip_detail->rate = $payslip_detail->rate;
    //         $payslip_detail->amount = $payslip_detail->amount;

    //         
    //         $previous_total = $yearly_payslip_data[$pay_detail_name] ?? 0;
    //         $payslip_detail->yearly_total = $previous_total;

    //         // Set category_id
    //         $payslip_detail->category_id = $payslip_detail->salary_item->salaryItemsCategory->id;

    //         // Unset unnecessary fields from the response
    //         unset(
    //             $payslip_detail->salary_item,
    //             $payslip_detail->id,
    //             $payslip_detail->created_at,
    //             $payslip_detail->updated_at,
    //             $payslip_detail->payslip_id,
    //             $payslip_detail->salary_item_id,
    //             $payslip_detail->payslip
    //         );
    //     }

    //     // Return the filtered details
    //     return $filtered_details->values();
    // }


    /**
     * Get yearly total for each salary item from January to previous month.
     */

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

   $yearToDateTaxPay = number_format($yearToDateTaxPay, 2, '.', '');

    return [
        'gross_pay' => $yearToDateGrossPay,
        'taxable_gross' => $yearToDateTaxableGross,
        'tax' => $yearToDateTaxPay,
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



public function get_yearly_payslip_totals_tax($employee_id, $current_month)
{
    $current_year = date('Y');

    $yearly_payslips = PayslipDetail::query()
        ->whereHas('payslip', function ($query) use ($employee_id, $current_year, $current_month) {
            $query->where('employee_id', $employee_id)
                  ->whereYear('first_date', $current_year)
                  ->whereMonth('first_date', '<=', $current_month); 
        })
        ->get();

    $yearly_totals = [];

    foreach ($yearly_payslips as $payslip) {
        $pay_detail_name = $payslip->salary_item->name;
        $amount = (float) $payslip->amount;

        if (!isset($yearly_totals[$pay_detail_name])) {
            $yearly_totals[$pay_detail_name] = 0;
        }

        $yearly_totals[$pay_detail_name] += $amount;
    }

    return $yearly_totals;
}


    

    public function get_base_amount_or_hours($get_base_amount_or_hours)
        {
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
                    'hours' => round(floatval($this->hours_worked), 2),
                    'overtime_hours' => round(floatval($this->getTotalOvertimeHours($this->payslip_details)), 2),
                    'total_fixed_pay' => round(floatval(($this->wages + $this->additional_pay) - $this->leave_deduction), 2),
                    'taxable_allowances' => round(floatval($this->taxable_allowance), 2),
                    'non_taxable_allowances' => round(floatval($this->non_taxable_allowance), 2),
                    'total_gross_pay' => round(floatval((($this->wages + $this->additional_pay) - $this->leave_deduction) + $this->taxable_allowance + $this->non_taxable_allowance), 2),
                    'taxable_gross_pay' => round(floatval(((($this->wages + $this->additional_pay) - $this->leave_deduction) + $this->taxable_allowance + $this->non_taxable_allowance) - $this->non_taxable_allowance), 2),
                    'ytd_tax_paid' => round(floatval($this->tax_value + $this->post_tax_value), 2),
                    'tax_amount' => round(floatval($this->tax_value + $this->post_tax_value), 2),
                    'total_staff_contribution' => round(floatval($this->total_employee_deduction), 2),
                    'total_company_contribution' => round(floatval($this->company_contribution), 2),
                    'total_staff_cost' => round(floatval((($this->wages + $this->additional_pay) - $this->leave_deduction) + $this->taxable_allowance + $this->non_taxable_allowance + $this->company_contribution), 2),
                    'total_net_pay' => $this->get_netPay($this->wages, $this->hours_worked, $this->net_pay),
                    'total_deductions' => round(floatval($this->tax_value + $this->post_tax_value + $this->total_employee_deduction), 2),
                ],
            ],
            'yearly' => [
                [
                    'hours' => round(floatval($this->get_yearly_data("hours")['hours']), 2),
                    'overtime_hours' => round(floatval($this->getTotalOvertimeHours($this->payslip_details)), 2),
                    'total_fixed_pay' => round(floatval($this->get_yearly_data("total_fixed_pay")['total_fixed_pay']), 2),
                    'taxable_allowances' => round(floatval($this->get_yearly_data("taxable_allowances")['taxable_allowances']), 2),
                    'non_taxable_allowances' => round(floatval($this->get_yearly_data("non_taxable_allowances")['non_taxable_allowances']), 2),
                    'total_gross_pay' => round(floatval($this->get_yearly_data("total_gross_pay")['total_gross_pay']), 2),
                    'taxable_gross_pay' => round(floatval($this->get_yearly_data("taxable_gross_pay")['taxable_gross_pay']), 2),
                    'ytd_tax_paid' => round(floatval($this->get_yearly_data("ytd_tax_paid")['ytd_tax_paid']), 2),
                    'tax_amount' => round(floatval($this->get_yearly_data("tax_amount")['tax_amount']), 2),
                    'total_staff_contribution' => round(floatval($this->get_yearly_data("total_staff_contribution")['total_staff_contribution']), 2),
                    'total_company_contribution' => round(floatval($this->get_yearly_data("total_company_contribution")['total_company_contribution']), 2),
                    'total_staff_cost' => round(floatval($this->get_yearly_data("total_staff_cost")['total_staff_cost']), 2),
                    'total_net_pay' => round(floatval($this->get_yearly_data("total_net_pay")['total_net_pay']), 2),
                    'total_deductions' => round(floatval(
                        $this->get_yearly_data("tax_amount")['tax_amount'] + 
                        $this->get_yearly_data("total_staff_contribution")['total_staff_contribution']
                    ), 2),
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
