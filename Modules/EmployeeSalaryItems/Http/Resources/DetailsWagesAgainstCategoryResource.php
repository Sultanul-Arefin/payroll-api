<?php

namespace Modules\EmployeeSalaryItems\Http\Resources;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Arr;
use JsonSerializable;

class DetailsWagesAgainstCategoryResource extends JsonResource
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
            $this->merge(
                Arr::only(parent::toArray($request), [
                    'id',
                ])
            ),
            'item_name' => $this->salaryItemsName?->name,
            'category' => $this->salaryItemsName?->salaryItemsCategory?->name,
            'salary_amount' => $this->getSalaryAmount($this->salaryItemsName?->salaryItemsCategory, $this->amount),
            'issue_to' => $this->is_general == 1 ? "All" : $this->employee?->name,
            'is_general' => $this->is_general, // 1 => All, 0 => For Specific employees
            'is_percentage' => $this->is_percentage, // 1 => true, 0 => false
            'is_threshold' => $this->salaryItemsName->is_threshold == 2 ? 1 : 0 // 1 => true, 0 => false
        ];
    }

    public function getSalaryAmount($category, $amount)
    {
        if($category->id == 7)
        {
            // return "company_deduction => {$this->deduction_details?->government_or_company_amount}, employee_contribution => {$this->deduction_details?->employee_amount}";
            return [
                'company_deduction' => $this->deduction_details?->government_or_company_amount,
                'employee_contribution' => $this->deduction_details?->employee_amount
            ];
        } elseif($category->id == 8)
        {
            // return "company_deduction => {$this->deduction_details?->government_or_company_amount}, employee_deduction => {$this->deduction_details?->employee_amount}";
            return [
                'company_deduction' => $this->deduction_details?->government_or_company_amount,
                'employee_deduction' => $this->deduction_details?->employee_amount
            ];
        } elseif($category->id == 5 && $this->salaryItemsName->is_threshold == 2)
        {
            return [
                'amount' => $amount,
                'start_percentage_after' => $this->threshold_details->start_percentage_after,
                'end_percentage_at' => $this->threshold_details->end_percentage_at,
            ];
        } else{
            return $amount;
        }
    }
}
