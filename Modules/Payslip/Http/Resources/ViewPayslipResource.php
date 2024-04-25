<?php

namespace Modules\Payslip\Http\Resources;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use JsonSerializable;
use Modules\Payslip\Entities\PayslipDetailsForDeduction;

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
            'fixed_pay_details' => $this->wages,
            'additional_pay' => 0, // additional pay goes here
            'wage_deduction' => $this->leave_deduction,
            'total_fixed_pay' => $this->wages - $this->leave_deduction,
            'taxable_allowance' => $this->taxable_allowance,
            'non_taxable_allowance' => $this->non_taxable_allowance,
            'total_gross_pay' => $this->gross_pay_before_tax,
            'taxable_gross_pay' => $this->gross_pay_before_tax - $this->non_taxable_allowance,
            'tax_amount' => $this->tax_value + $this->post_tax_value,
            'gross_pay_after_tax' => $this->gross_pay_after_tax,
            'pay_due_before_deduction' => $this->pay_due_before_deduction,
            'staff_social_charges' => $this->get_staff_social_charges(), // staff social charge goes here
            'total_net_pay' => $this->net_pay,
            'overall_calculation' => $this->overall_calculation(),
            'social_decution' => $this->get_social_deduction(),
            'other_decution' => $this->get_other_deduction()
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

    public function get_staff_social_charges()
    {
        return PayslipDetailsForDeduction::query()
                ->where('payslip_id', $this->id)
                ->sum('employee_amount');
    }

    public function get_payslip_details($payslip_details){
        foreach($payslip_details as $payslip_detail){
            $payslip_detail->pay_details = $payslip_detail->salary_item->name;
            $payslip_detail->base_amount_or_hours = $payslip_detail->base_amount_or_hours;
            $payslip_detail->rate = $payslip_detail->amount;
            $payslip_detail->category_id = $payslip_detail?->salary_item?->salaryItemsCategory?->id;

            // unset these keys from the response
            unset($payslip_detail->salary_item, $payslip_detail->id, $payslip_detail->amount, $payslip_detail->created_at, $payslip_detail->updated_at, $payslip_detail->payslip_id, $payslip_detail->salary_item_id);
        }
        return $payslip_details;
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
