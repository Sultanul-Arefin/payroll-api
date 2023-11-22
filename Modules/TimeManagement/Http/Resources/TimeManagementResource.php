<?php

namespace Modules\TimeManagement\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class TimeManagementResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'year' => date('Y'),
            'name' => $this->name,
            'employee_id' => '',
            'department' => '',
            'annual_leave_quota' => '',
            'annual_leave_taken' => '',
            'remaining_annual_leave' => '',
            'sick_leave_quota' => '',
            'sick_leave_taken' => '',
            'unpaid_sick_leave(absent)' => '',
            'maternity_leave' => ''
        ];
    }
}
