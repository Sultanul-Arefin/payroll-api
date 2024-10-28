<?php

namespace Modules\Dashboard\app\Transformers;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DigitalSocialReportResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'employee_name' => $this->employee?->name,
            'details' => null,
            'staff_contribution' => $this->employee_contribution_value,
            'company_contribution' => $this->company_contribution_value,
            'combined_contribution' => $this->employee_contribution_value + $this->company_contribution_value
            // 'tax_no' => $this->employee?->user_deatils?->fax,
            // 'month' => $this->month,
            // 'year' => date('Y', strtotime($this->first_date)),
            // 'gross_pay_before_tax' => $this->gross_pay_before_tax,
            // 'income_tax' => $this->tax_value,
            // 'other_tax' => $this->tax_value,
            // 'total_paid' => $this->net_pay,
        ];
    }
}
