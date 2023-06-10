<?php

namespace Modules\Attendance\Http\Services;

use Illuminate\Database\Eloquent\Model;
use Modules\Attendance\Entities\Attendance;
use Modules\Attendance\Entities\AttendanceDetail;

class AttendanceService{
    function checkIfAttendanceExist($date) : ?Model {
        $attendance = Attendance::where('dates', $date)->first();
        if($attendance){
            return $attendance;
        } else{
            return false;
        }
    }

    function checkForSameTime($attendance, $in_time, $out_time) {
        $details = AttendanceDetail::where('attendance_id', $attendance->id)->whereTime('in_time', '>=', $in_time)->whereTime('in_time', '<=', $out_time)->get();
        return $details;
        return $attendance->attendance_details;
    }
}