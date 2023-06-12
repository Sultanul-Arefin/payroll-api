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
        $attendance = AttendanceDetail::where('attendance_id', $attendance->id)->get();
            foreach($attendance as $value){
                if(strtotime($value->in_time) >= strtotime($in_time) && strtotime($value->out_time) <= strtotime($out_time)){
                    return true;
                } else{
                    return false;
                }
            }
    }
}