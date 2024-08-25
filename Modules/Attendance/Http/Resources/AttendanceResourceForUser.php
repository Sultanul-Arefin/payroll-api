<?php

namespace Modules\Attendance\Http\Resources;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use JsonSerializable;
use Modules\Attendance\Entities\Attendance;
use Modules\Attendance\Entities\AttendanceDetail;

class AttendanceResourceForUser extends JsonResource
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
            'id' => $this->id,
            'date' => $this->dates,
            'status' => $this->getStatus($this->status),
            'details' => $this->attendance_details->map(function ($attendance) {
                return [
                    'id' => $attendance->id,
                    'in_time' => $attendance->in_time,
                    'out_time' => $attendance->out_time,
                    'office_type' => $attendance->office_type == AttendanceDetail::FROM_OFFICE ? "OFFICE" : "HOME"
                ];
            }),
            'created_at' => $this->created_at->format('H:i:s'),
            'is_deletable' => $this->getStatus($this->status) == "pending" ? 1 : 0,
            'is_editable' => $this->getStatus($this->status) == "pending" ? 1 : 0,
            'working_hours_per_day' => $this->user?->company?->working_hours_per_day,
            'lunch_and_others_per_day' => $this->user?->company?->lunch_and_others_per_day,
            'total_office_hours' => $this->user?->company?->working_hours_per_day + $this->user?->company?->lunch_and_others_per_day,
            'in_time' => $this->attendance_details[0]?->in_time, // have to recheck this value
            'out_time' => $this->attendance_details[0]?->out_time // have to recheck this value
        ];
    }

    public function getStatus($status): ?string
    {
        if ($status == Attendance::ABSENT) {
            return 'absent';
        } elseif ($status == Attendance::PENDING) {
            return 'pending';
        } elseif ($status == Attendance::RESTRICTED) {
            return 'restricted';
        }
        return 'approved';
    }
}
