<?php

namespace Modules\Payslip\Http\Resources;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Arr;
use JsonSerializable;

class PayslipResource extends JsonResource
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
                    'month',
                ])
            ),
            'employee' => $this->employee?->only('name', 'email'),
            'employee_payslip_type' => $this->employee?->user_details?->payslip_type,
            'payment_date' => $this->payment_date,
            'department' => $this->employee?->department?->department_name
        ];
    }
}
