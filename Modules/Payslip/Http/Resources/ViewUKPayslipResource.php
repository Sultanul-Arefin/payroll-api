<?php

namespace Modules\Payslip\Http\Resources;

use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use JsonSerializable;
use Modules\Payslip\Entities\Payslip;
use Modules\Payslip\Entities\PayslipDetailsForDeduction;

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
            'additional_pay' => $this->taxable_allowance,
            //'wage_deduction' => $this->taxable_allowance,
            'total_fixed_pay' => $this->taxable_allowance,
            'taxable_allowance' => $this->taxable_allowance,
            'non_taxable_allowance' => $this->non_taxable_allowance,
            //'total_gross_pay' => $this->gross_pay_before_tax,
            'total_gross_pay' => $this->wages - $this->leave_deduction + $this->taxable_allowance + $this->non_taxable_allowance,
            //'taxable_gross_pay' => $this->gross_pay_before_tax - $this->non_taxable_allowance,
            'taxable_gross_pay' => ($this->wages - $this->leave_deduction + $this->taxable_allowance),
            'tax_amount' => $this->tax_value + $this->post_tax_value,
            'gross_pay_after_tax' => $this->gross_pay_after_tax,
            'pay_due_before_deduction' => $this->pay_due_before_deduction,
            'social_deduction' => $this->get_social_deduction(),
            'other_deduction' => $this->get_other_deduction(),
            'total_deductions' => $this->total_employee_deduction,
            'staff_social_charges' => 0.00,
           'income_tax' => $this->getIncomeTax($this->payslip_details),
            'total_net_pay' => $this->net_pay,
           // 'overall_calculation' => $this->overall_calculation(),
            'year_to_date' => $this->getYearToDateCalculations(),
        
        ];
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
        foreach($deduction_details as $value)
        {
            array_push($response, [
                'title' => $value?->salary_item_name?->name,
                'base' => $this->wages,
                'employee_rate' => $value->employee_amount,
                'employee_amount' => $value->employee_amount,
                'company_rate' => $value->government_or_company_amount,
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
                'base' => $this->wages,
                'employee_rate' => $value->employee_amount,
                'employee_amount' => $value->employee_amount,
                'company_rate' => $value->government_or_company_amount,
                'company_amount' => $value->government_or_company_amount
            ]);
        }
        return $response;
    }

   

    public function get_payslip_details($payslip_details){

        
        $payslip_value = 0;
        foreach($payslip_details as $payslip_detail){
            $payslip_detail->pay_details = $payslip_detail->salary_item->name;
            $payslip_detail->base_amount_or_hours = $payslip_detail->base_amount_or_hours;
            $payslip_detail->rate = $payslip_detail->amount;
            $payslip_detail->category_id = $payslip_detail?->salary_item?->salaryItemsCategory?->id;

            // payslip_calculation
            $payslip_value += $payslip_detail->amount;
            // unset these keys from the response
            unset($payslip_detail->salary_item, $payslip_detail->id, $payslip_detail->amount, $payslip_detail->created_at, $payslip_detail->updated_at, $payslip_detail->payslip_id, $payslip_detail->salary_item_id);
        }
        return [
            'payslip_details' => $payslip_details,
            'total_payslip_value' => $payslip_value
        ];
    }

    private function getIncomeTax($payslipDetails)
{
    $filteredDetails = [];
    $totalAmount = 0;

    foreach ($payslipDetails as $detail) {
        if (in_array($detail['category_id'], [5, 6])) { // Check if category_id is 5 or 6
            $filteredDetails[] = [
                'pay_details' => $detail['pay_details'],
                'base_amount_or_hours' => $detail['base_amount_or_hours'],
                'rate' => $detail['rate'],
            ];
            $totalAmount += $detail['rate']; // Accumulate the total rate
        }
    }

    return [
        'filtered_details' => $filteredDetails,
        'total_amount' => $totalAmount,
        'total_cost'=>$totalAmount + $this->total_employee_deduction,
    ];
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
    public function getYearToDateCalculations()
{
    $startOfYear = now()->startOfYear();
    $yearToDatePayslips = Payslip::where('employee_id', $this->employee->id)
                        ->whereBetween('payment_date', [$startOfYear, $this->payment_date])
                        ->get();

    // Calculate yearly gross pay
    $yearToDateGrossPay = $yearToDatePayslips->sum(function($payslip) {
        return $payslip->wages - $payslip->leave_deduction + $payslip->taxable_allowance + $payslip->non_taxable_allowance;
    });

    // Calculate yearly taxable gross pay
    $yearToDateTaxableGross = $yearToDatePayslips->sum(function($payslip) {
        return $payslip->wages - $payslip->leave_deduction + $payslip->taxable_allowance;
    });

    // Calculate yearly tax
    $yearToDateTax = $yearToDatePayslips->sum(function($payslip) {
        return $payslip->tax_value + $payslip->post_tax_value;
    });

    return [
        'gross_pay' => $yearToDateGrossPay,
        'taxable_gross' => $yearToDateTaxableGross,
        'tax' => $yearToDateTax,
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
