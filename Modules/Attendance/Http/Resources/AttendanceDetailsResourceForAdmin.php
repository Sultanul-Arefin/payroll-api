<?php

namespace Modules\Attendance\Http\Resources;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use JsonSerializable;
use Modules\Attendance\Entities\AttendanceDetail;

class AttendanceDetailsResourceForAdmin extends JsonResource
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
            'user_name' => $this->user->name,
            'user_email' => $this->user->email,
            'date' => $this->dates,
            'attendance_details' => $this->getAttendanceDetails($this->attendance_details),
            'created_at' => $this->created_at,
        ];
    }

    public function getAttendanceDetails($attendance_details)
    {
        $attendance_details->each(function($detail){
            $detail->office_type = $detail->office_type == AttendanceDetail::FROM_OFFICE ? "OFFICE" : "HOME";
        });
        return $attendance_details;
    }
}
