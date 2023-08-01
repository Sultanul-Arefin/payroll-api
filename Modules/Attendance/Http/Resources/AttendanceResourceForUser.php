<?php

namespace Modules\Attendance\Http\Resources;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Arr;
use JsonSerializable;
use Modules\Attendance\Entities\Attendance;

class AttendanceResourceForUser extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array|Arrayable|JsonSerializable
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'date' => $this->dates,
            'status' => $this->getStatus($this->status),
            'details' => $this->attendance_details->map(function($attendance){
                return [
                    'in_time' => $attendance->in_time,
                    'out_time' => $attendance->out_time
                ];
            })
        ];
    }

    public function getStatus($status):? string
    {
        if($status == Attendance::ABSENT){
            return 'absent';
        } elseif($status == Attendance::PENDING){
            return 'pending';
        } elseif($status == Attendance::RESTRICTED){
            return 'restricted';
        }
        return 'approved';
    }

    public function getValue($value)
    {
        return $value->dates;
    }
}
