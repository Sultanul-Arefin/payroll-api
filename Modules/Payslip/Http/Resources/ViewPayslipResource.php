<?php

namespace Modules\Payslip\Http\Resources;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use JsonSerializable;

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
            'payment_date' => date('Y-m-d H:i:s'),
            'fixed_pay_details' => $this->wages,
            'additional_pay' => 0,
            'wage_deduction' => $this->leave_deduction,
            'total_fixed_pay' => $this->wages - $this->leave_deduction,
            'taxable_allowance' => $this->taxable_allowance,
            'non_taxable_allowance' => $this->non_taxable_allowance,
            'total_gross_pay' => $this->gross_pay_before_tax,
            'taxable_gross_pay' => $this->gross_pay_before_tax - $this->non_taxable_allowance,
            'tax_amount' => $this->tax_value + $this->post_tax_value,
            'pay_due_before_deduction' => $this->pay_deduction,
            'staff_social_charges' => 0.00,
            'total_net_pay' => $this->net_pay,
            'overall_calculation' => $this->overall_calculation(),
        ];
    }

    public function get_payslip_details($payslip_details){
        foreach($payslip_details as $payslip_detail){
            $payslip_detail->salary_item_name = $payslip_detail->salary_item->name;
        }
        return $payslip_details;
    }

    public function overall_calculation()
    {
        return [
            'monthly' => [
                [
                    'hours' => $this->hours_worked,
                    'overtime_hours' => 0,
                    'total_fixed_pay' => $this->wages - $this->leave_deduction,
                    'taxable_allowances' => $this->taxable_allowance,
                    'non_taxable_allowances' => $this->non_taxable_allowance,
                    'total_gross_pay' => $this->gross_pay_before_tax,
                    'taxable_gross_pay' => $this->gross_pay_before_tax - $this->non_taxable_allowance,
                    'ytd_tax_paid' => 0,
                    'total_staff_contribution' => 0,
                    'total_company_contribution' => 0,
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
                    'total_staff_contribution' => 0,
                    'total_company_contribution' => 0,
                    'total_staff_cost' => $this->net_pay,
                    'total_net_pay' => $this->net_pay,
                ],
            ],
            'total_net_pay' => $this->net_pay,
        ];
    }
}
