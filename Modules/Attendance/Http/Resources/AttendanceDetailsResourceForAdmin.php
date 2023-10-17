<?php

namespace Modules\Attendance\Http\Resources;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Arr;
use JsonSerializable;
use Modules\Attendance\Entities\Attendance;

class AttendanceDetailsResourceForAdmin extends JsonResource
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
            'user_name' => $this->user->name,
            'user_email' => $this->user->email,
            'date' => $this->dates,
            'attendance_details' => $this->attendance_details
        ];
    }
}
