<?php

namespace Modules\Payslip\Http\Resources;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use JsonSerializable;

class ViewFrenchPayslipResource extends JsonResource
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
            'items_details' => $this->payslip_details,
            'payment_date' => date('Y-m-d H:i:s'),
            'fixed_pay_details' => $this->taxable_allowance,
            'additional_pay' => $this->taxable_allowance,
            'wage_deduction' => $this->taxable_allowance,
            'total_fixed_pay' => $this->taxable_allowance,
            'taxable_allowance' => $this->taxable_allowance,
            'non_taxable_allowance' => $this->non_taxable_allowance,
            'total_gross_pay' => $this->gross_pay_before_tax,
            'taxable_gross_pay' => $this->gross_pay_before_tax,
            'tax_amount' => $this->tax_value + $this->post_tax_value,
            'pay_due_before_deduction' => $this->pay_deduction,
            'staff_social_charges' => 0.00,
            'total_net_pay' => $this->net_pay,
            'overall_calculation' => $this->overall_calculation(),
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
