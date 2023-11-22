<?php

namespace Modules\TimeManagement\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class CalendarOverviewResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'date' => $this->dates,
            'entry_time' => '',
            'exit_time' => '',
            'details' => '',
            'total_office_hours' => '',
            'hours_worked' => '',
            'lunch_and_other_hour' => '',
            'description' => '',
            'overtime' => '',
            'double_overtime' => '',
            'recuperated' => '',
            'early_day_departure' => '',
            'status' => ''
        ];
    }
}
