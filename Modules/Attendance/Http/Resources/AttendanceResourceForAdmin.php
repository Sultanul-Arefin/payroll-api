<?php

namespace Modules\Attendance\Http\Resources;

use Illuminate\Contracts\Support\Arrayable;
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
        ];
    }

    public function count_appearances($date)
    {
        return Attendance::where('dates', $date)->count();
    }

    public function get_info($date)
    {
        $attendances = Attendance::where('dates', $date)->get();

        return AttendanceDetailsResourceForAdmin::collection($attendances);
    }
}
