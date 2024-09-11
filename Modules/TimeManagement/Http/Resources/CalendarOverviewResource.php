<?php

namespace Modules\TimeManagement\Http\Resources;

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
            'overtime' => $this->getOvertime(),
            'double_overtime' => $this->getDoubleOvertime(),
            'recuperated' => $this->getRecuperated(),
            'early_day_departure' => $this->getEarlyDayDeparture(),
            'status' => $this->getStatus($this->status)
        ];
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

    function getOvertime() {
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
