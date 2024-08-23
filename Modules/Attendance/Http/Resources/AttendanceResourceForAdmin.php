<?php

namespace Modules\Attendance\Http\Resources;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use JsonSerializable;
use Modules\Attendance\Entities\Attendance;

class AttendanceResourceForAdmin extends JsonResource
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
            'date' => $this->dates,
            'count' => $this->count_appearances($this->dates),
            'details' => $this->get_info($this->dates),
            'created_at' => $this->created_at?->format('H:i:s'),
            'working_hours_per_day' => $this->user?->company?->working_hours_per_day,
            'lunch_and_others_per_day' => $this->user?->company?->lunch_and_others_per_day,
            'total_office_hours' => $this->user?->company?->working_hours_per_day + $this->user?->company?->lunch_and_others_per_day,
            'in_time' => $this->attendance_details[0]?->in_time,
            'out_time' => $this->attendance_details[0]?->out_time
        ];
    }

    public function count_appearances($date)
    {
        return Attendance::query()
                ->whereHas(
                    'user', function(Builder $builder){
                        $builder->where('company_id', auth()->user()->company_id);
                    }
                )
                ->where('dates', $date)->count();
    }

    public function get_info($date)
    {
        $attendances = Attendance::query()
                    ->whereHas(
                        'user', function(Builder $builder){
                            $builder->where('company_id', auth()->user()->company_id);
                        }
                    )
                    ->where('status', Attendance::PENDING)
                    ->where('dates', $date)->get();

        return AttendanceDetailsResourceForAdmin::collection($attendances);
    }
}
