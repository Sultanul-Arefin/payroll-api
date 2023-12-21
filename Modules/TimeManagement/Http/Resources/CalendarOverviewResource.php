<?php

namespace Modules\TimeManagement\Http\Resources;

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
            'details' => $this->getDetails(),
            'total_office_hours' => $this->getTotalOfficeHours(),
            'hours_worked' => $this->getHoursWorked(),
            'lunch_and_other_hour' => $this->getLunchAndOtherHour(),
            'description' => $this->getDescription(),
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

    function getDetails() {
        return 'Office';
    }

    function getTotalOfficeHours() {
        return $this->user->company->working_hours_per_day;
    }

    function getHoursWorked() {
        return 0;
    }

    function getLunchAndOtherHour() {
        return $this->user->company->lunch_and_others_per_day;
    }

    function getDescription() {
        return 'Office';
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
