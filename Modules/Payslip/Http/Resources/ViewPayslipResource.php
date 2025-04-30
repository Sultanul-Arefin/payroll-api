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

class ViewPayslipResource extends JsonResource
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
            'payment_date' => $this->payment_date, //date('Y-m-d H:i:s')
            'fixed_pay_details' => round(floatval($this->wages),2),
            'additional_pay' =>round(floatval($this->additional_pay),2), // additional pay goes here
            'wage_deduction' => round(floatval($this->leave_deduction),2),
            'total_fixed_pay' => round(floatval(($this->wages + $this->additional_pay) - $this->leave_deduction),2),
            'taxable_allowance' => round(floatval($this->taxable_allowance),2),
            'non_taxable_allowance' => round(floatval($this->non_taxable_allowance),2),
            'total_gross_pay' => round(floatval((($this->wages + $this->additional_pay) - $this->leave_deduction) + $this->taxable_allowance + $this->non_taxable_allowance),2), // this is accurate
            // 'total_gross_pay' => $this->gross_pay_before_tax,
            // 'taxable_gross_pay' => $this->gross_pay_before_tax, // this is added due to question from Nabila. Have to check the calculation again
            'taxable_gross_pay' =>round(floatval(((($this->wages + $this->additional_pay) - $this->leave_deduction) + $this->taxable_allowance + $this->non_taxable_allowance) - $this->non_taxable_allowance),2), // this is accurate // total_gross_pay - non_taxable_allowance
            // 'taxable_gross_pay' => $this->gross_pay_before_tax - $this->non_taxable_allowance,
            'tax_amount' =>round(floatval($this->tax_value + $this->post_tax_value),2),
            'gross_pay_after_tax' => round(floatval($this->gross_pay_after_tax),2),
            'pay_due_before_deduction' => round(floatval($this->pay_due_before_deduction),2),
            'staff_social_charges' => $this->get_staff_social_charges(), // staff social charge goes here
            //'total_net_pay' =>round(floatval($this->net_pay),2),
            'total_net_pay' =>$this->get_netPay($this->wages,$this->hours_worked,$this->net_pay),
            'overall_calculation' => $this->overall_calculation(),
            'social_deduction' => $this->get_social_deduction(),
            'other_deduction' => $this->get_other_deduction(),
            'total_social_deduction' => $this->get_total_social_deduction(),
            'total_other_deduction' => $this->get_total_other_deduction(),
            'annual_leave' => $this->get_annual_leave_calculation($this->employee)
        ];
    }
    public function get_netPay($wages,$hours_worked,$net_pay){
        if ($wages<=0 && $hours_worked<=0 ){
            return 0 . "(Payslip Zero as no attendance given process attendance to auto run or run Payslip manual)";
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
            'remaining_annual_leave' => $this->getAnnualLeaveQuota() - $this->getTotalAnnualLeaveTaken($this->employee->id)
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
        // $annual_leave_data = LeaveSalaryItems::query()
        //     ->whereHas(
        //         'salary_items_name', function(Builder $builder){
        //             $builder
        //             ->where(
        //                 'name',
        //                 'Annual Leave'
        //             )->where(
        //                 'company_id',
        //                 auth()->user()->company_id
        //             );
        //         }
        //     )
        //     ->first();
        // $data = UserLeave::query()
        //         ->where('user_id', $user_id)
        //         ->where('leave_type', $annual_leave_data->salary_items_id)
        //         ->where('status', UserLeave::APPROVED)
        //         ->get();
        // $count = 0;
        // foreach($data as $value){
        //     $details = UserLeaveDetail::query()
        //         ->where('user_leaves_id', $value->id)
        //         ->count();
        //     $count += $details;
        // }
        // return $count;
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
        foreach($deduction_details as $value)
        {
            array_push($response, [
                'title' => $value?->salary_item_name?->name,
                'base' => $this->gross_pay_before_tax,
                'employee_rate' => $value->employee_amount_rate,
                'employee_amount' => $value->employee_amount,
                'company_rate' => $value->government_or_company_amount_rate,
                'company_amount' => $value->government_or_company_amount
            ]);
        }
        return $response;
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
        foreach($deduction_details as $value)
        {
            array_push($response, [
                'title' => $value?->salary_item_name?->name,
                'base' => $this->gross_pay_before_tax,
                'employee_rate' => $value->employee_amount_rate,
                'employee_amount' => $value->employee_amount,
                'company_rate' => $value->government_or_company_amount_rate,
                'company_amount' => $value->government_or_company_amount
            ]);
        }
        return $response;
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
        return round(floatval($total),2);
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
        return round(floatval($total),2);
    }

    public function get_staff_social_charges()
    {
        return round(floatval(
             PayslipDetailsForDeduction::query()
                ->where('payslip_id', $this->id)
                ->sum('employee_amount')),2);
    }

    public function get_payslip_details($payslip_details){
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

            // unset these keys from the response
            unset($payslip_detail->salary_item, $payslip_detail->id, $payslip_detail->created_at, $payslip_detail->updated_at, $payslip_detail->payslip_id, $payslip_detail->salary_item_id);
        }
        return $filtered_details->values();
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
                    'hours' => round(floatval($this->hours_worked), 2),
                    'overtime_hours' => round(floatval($this->getOvertimeHours($this->payslip_details)), 2),
                    'total_fixed_pay' => round(floatval(($this->wages + $this->additional_pay) - $this->leave_deduction), 2),
                    'taxable_allowances' => round(floatval($this->taxable_allowance), 2),
                    'non_taxable_allowances' => round(floatval($this->non_taxable_allowance), 2),
                    'total_gross_pay' => round(floatval(
                        (($this->wages + $this->additional_pay) - $this->leave_deduction) +
                        $this->taxable_allowance + $this->non_taxable_allowance
                    ), 2),
                    'taxable_gross_pay' => round(floatval(
                        ((($this->wages + $this->additional_pay) - $this->leave_deduction) +
                        $this->taxable_allowance + $this->non_taxable_allowance) -
                        $this->non_taxable_allowance
                    ), 2),
                    'ytd_tax_paid' => round(floatval($this->tax_value + $this->post_tax_value), 2),
                    'tax_amount' => round(floatval($this->tax_value + $this->post_tax_value), 2),
                    'total_staff_contribution' => round(floatval($this->total_employee_deduction), 2),
                    'total_company_contribution' => round(floatval($this->company_contribution), 2),
                    // total_staff_cost = total_gross_pay + company contribution
                    'total_staff_cost' => round(floatval(
                        (($this->wages + $this->additional_pay) - $this->leave_deduction) +
                        $this->taxable_allowance + $this->non_taxable_allowance + $this->company_contribution
                    ), 2),
                    'total_net_pay' =>$this->get_netPay($this->wages,$this->hours_worked,$this->net_pay),
                ],
            ],
            'yearly' => [ // yearly one column will be updated
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
