<?php

namespace Modules\Attendance\Http\Resources;

use App\Models\Overtime;
use Carbon\Carbon;
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
            'lunch_and_others_per_day' => $this->getLunchAndOtherHour($this->id),
            'total_office_hours' => $this->user?->company?->working_hours_per_day + $this->user?->company?->lunch_and_others_per_day,
            'in_time' => $this->attendance_details[0]?->in_time, // have to recheck this value
            'out_time' => $this->attendance_details[0]?->out_time, // have to recheck this value,
            'hours_worked' => $this->getHoursWorked($this->id),
            'is_overtime' => $this->overtime ? 1 : 0,
            'overtime_value' => $this->getOvertime($this->overtime)
        ];
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

    function getHoursWorked($attendance_id) {
        $details = AttendanceDetail::where('attendance_id', $attendance_id)->get();
        $hours = 0;
        $minutes = 0;
        $seconds = 0;
        $time = 0;
        foreach($details as $detail){
            $inTime = Carbon::createFromFormat('H:i:s', $detail->in_time);
            $outTime = Carbon::createFromFormat('H:i:s', $detail->out_time);

            $totalMinutesWorked = $inTime->diffInMinutes($outTime);
            $time += $totalMinutesWorked / 60;
        }
        // if total attendance time is greater than 4 hours, then the lunch time will count
        // A RANDOM LOGIC FROM BRIAN FINN
        if($time>4){
            return $time - auth()->user()->company?->lunch_and_others_per_day;
        }
        return $time;
    }

    function getLunchAndOtherHour($attendance_id) {
        $get_time = $this->getHoursWorked($attendance_id);
        if($get_time > 4){
            return $this->user->company->lunch_and_others_per_day;
        }
        return 0;
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
