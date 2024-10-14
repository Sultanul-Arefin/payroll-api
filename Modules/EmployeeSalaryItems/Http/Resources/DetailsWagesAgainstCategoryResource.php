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
            'is_general' => 0,
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
        } else{
            return $amount;
        }
    }
}
