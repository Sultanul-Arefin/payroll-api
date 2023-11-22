<?php

namespace Modules\TimeManagement\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class AttendanceReportResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'name' => $this->name,
            'department_name' => '',
            'present_days' => '',
            'overtime' => '',
            'double_overtime' => '',
            'early_departure' => '',
            'annual_leave' => '',
            'sick_leave' => '',
            'unpaid_sick_leave(absent)' => '',
            'recuperated_hour' => ''
        ];
    }
}
