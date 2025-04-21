<?php

namespace Modules\TimeManagement\Http\Resources;

use App\Models\Overtime;
use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Attendance\Entities\Attendance;
use Modules\Attendance\Entities\AttendanceDetail;

class CalendarOverviewResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'date' => $this->dates,
            'entry_time' => $this->getEntryTime($this->id),
            'exit_time' => $this->getExitTime($this->id),
            'details' => $this->getDetails($this->id),
            'total_office_hours' => $this->getTotalOfficeHours(),
            'hours_worked' => $this->getHoursWorked($this->id),
            'lunch_and_other_hour' => $this->getLunchAndOtherHour($this->id),
            'description' => $this->getDescription($this->id),
            'overtime' => $this->getOvertimes(),
            'double_overtime' => $this->getDoubleOvertime(),
            'recuperated' => $this->getRecuperated(),
            'early_day_departure' => $this->getEarlyDayDeparture(),
            'status' => $this->getStatus($this->status),
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

    function getEntryTime($attendance_id) {
        $details = AttendanceDetail::query()
                ->where('attendance_id', $attendance_id)
                ->get();
        return $details[0]->in_time;
    }

    function getExitTime($attendance_id) {
        $details = AttendanceDetail::query()
                ->where('attendance_id', $attendance_id)
                ->get();
        $count = count($details);
        return $details[$count-1]->out_time;
    }

    function getDetails($attendance_id) {
        $details = AttendanceDetail::where('attendance_id', $attendance_id)->first();
        if($details->office_type == AttendanceDetail::FROM_HOME)
        {
            return "Home";
        }
        return "Office";
    }

    function getTotalOfficeHours() {
        return $this->user?->company?->working_hours_per_day;
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

    function getDescription($attendance_id) {
        $details = AttendanceDetail::where('attendance_id', $attendance_id)->first();
        if($details->office_type == AttendanceDetail::FROM_HOME)
        {
            return "Home";
        }
        return "Office";
    }

    function getOvertimes() {
        return 0;
    }

    function getDoubleOvertime() {
        return 0;
    }

    function getRecuperated() {
        return 0;
    }

    function getEarlyDayDeparture() {
        return 0;
    }

    function getStatus($status) {
        return match ($status) {
            Attendance::ABSENT => 'ABSENT',
            Attendance::PRESENT => 'PRESENT',
            Attendance::PENDING => 'PENDING',
            Attendance::RESTRICTED => 'RESTRICTED',
            default => 'Status Not Found'
        };
    }
}
