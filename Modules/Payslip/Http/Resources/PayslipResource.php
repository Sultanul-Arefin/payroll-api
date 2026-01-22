<?php

namespace Modules\Payslip\Http\Resources;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Arr;
use JsonSerializable;
use Modules\Payslip\Entities\Payslip;

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
            'employee' => $this->employee?->only('id','name', 'email'),
            'employee_payslip_type' => $this->employee?->user_details?->payslip_type,
            'payment_date' => $this->payment_date,
            'department' => $this->employee?->department?->department_name,
            'first_date' => $this->first_date,
            'last_date' => $this->last_date,
            'pay_frequency' => 'Monthly',
            'pay_type' => $this->pay_frequency == Payslip::PAY_FREQUENCY_HOURLY ? 'hourly' : 'monthly',
        ];
    }
}
