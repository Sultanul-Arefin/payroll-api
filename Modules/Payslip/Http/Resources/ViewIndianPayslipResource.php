<?php

namespace Modules\Payslip\Http\Resources;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use JsonSerializable;
use Modules\Payslip\Entities\PayslipDetailsForDeduction;

class ViewIndianPayslipResource extends JsonResource
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
            'payment_date' => $this->payment_date,
            'fixed_pay_details' => $this->wages,
            'additional_pay' => $this->taxable_allowance,
            'wage_deduction' => $this->taxable_allowance,
            'total_fixed_pay' => $this->taxable_allowance,
            'taxable_allowance' => $this->taxable_allowance,
            'non_taxable_allowance' => $this->non_taxable_allowance,
            'total_gross_pay' => $this->gross_pay_before_tax,
            'taxable_gross_pay' => $this->gross_pay_before_tax,
            'tax_amount' => $this->tax_value + $this->post_tax_value,
            'pay_due_before_deduction' => $this->pay_due_before_deduction,
            'staff_social_charges' => 0.00,
            'total_net_pay' => $this->net_pay,
            'overall_calculation' => $this->overall_calculation(),
        ];
    }

    public function get_deduction_details()
    {
        $deduction_details = PayslipDetailsForDeduction::query()
                        ->where('payslip_id', $this->id)
                        ->get();
        $deduction_value = 0;
        foreach($deduction_details as $dv)
        {
            $dv->pay_details = $dv->salary_item_name?->name;
             $dv->employee_amount = $dv->employee_amount;
            $dv->employee_amount = $dv->employee_amount;
            $dv->government_or_company_amount = $dv->government_or_company_amount;

            // deduction_calculation
            $deduction_value += $dv->employee_amount;

            unset($dv->payslip_id, $dv->salary_item_id, $dv->created_at, $dv->updated_at, $dv->pay_details, $dv->salary_item_name, $dv->id);
        }

        return [
            'deduction_details' => $deduction_details,
            'total_deduction_value' => $deduction_value
        ];
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
                    'total_net_pay' => $this->net_pay
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
