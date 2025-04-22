<?php

namespace Modules\Attendance\Http\Resources;

use App\Models\Overtime;
use App\Models\User;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use JsonSerializable;
use Modules\Attendance\Entities\Attendance;
use Modules\Attendance\Entities\AttendanceDetail;
use Modules\User\Entities\UserDetails;

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
            'created_at' => $this->created_at?->format('H:i:s'),
            'is_deletable' => $this->getStatus($this->status) == "pending" ? 1 : 0,
            'is_editable' => $this->getStatus($this->status) == "pending" ? 1 : 0,
            'is_overtime' => $this->overtime ? 1 : 0,
            'overtime_value' => $this->getOvertime($this->overtime),
            'attendance_type_key' => $this->user->user_details?->attendance_type,
            'attendance_type_value' => $this->user->user_details?->attendance_type == UserDetails::WEB_ATTENDANCE ? 'web' : 'machine',
            'total_office_hours' => $this->user->company?->working_hours_per_day + $this->user->company?->lunch_and_others_per_day,
            'employee_type' => $this->get_employee_type_for_manual_attendance($this->user)
            
        ];
    }

    public function get_employee_type_for_manual_attendance($user)
    {
        $type = $user->employee_type;
        return match($type){
            User::EMPLOYEE_TYPE_FULL_TIME => "full_time",
            User::EMPLOYEE_TYPE_PART_TIME => "part_time",
            User::EMPLOYEE_TYPE_FLEXI_TIME => "flexi_time",
            User::EMPLOYEE_TYPE_CONTRACTUAL => "contractual"
        };
    }

    public function getOvertime($overtime){
        if(!$overtime){
            return null;
        }
        return [
            'overtime_type' => $overtime->is_overtime,
            'overtime_type_value' => $this->overtime_type_value($overtime->is_overtime),
            'hour' => $overtime->hour
        ];
    }

    public function overtime_type_value($type){
        return match($type){
            Overtime::OVERTIME => "overtime",
            Overtime::DOUBLE_OVERTIME => "double_overtime",
            Overtime::RECUPERATED => "recuperated",
            Overtime::EARLY_DAY_DEPARTURE => "early_day_departure"
        };
    }

    public function getAttendanceDetails($attendance_details)
    {
        $attendance_details->each(function($detail){
            $detail->office_type = $detail->office_type == AttendanceDetail::FROM_OFFICE ? "OFFICE" : "HOME";
        });
        return $attendance_details;
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
