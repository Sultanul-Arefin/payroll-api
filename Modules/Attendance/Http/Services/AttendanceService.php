<?php

namespace Modules\Attendance\Http\Services;

use Illuminate\Database\Eloquent\Model;
use Modules\Attendance\Entities\Attendance;
use Modules\Attendance\Entities\AttendanceDetail;

class AttendanceService
{
    public function checkIfAttendanceExist($date): ?Model
    {
        $attendance = Attendance::where('dates', $date)->first();
        if ($attendance) {
            return $attendance;
        } else {
            return null;
        }
    }

    public function attendanceIsPossible($attendance, $in_time, $out_time)
    {

        $attendances = AttendanceDetail::query();
        $attendance = $attendances->where('attendance_id', $attendance->id)
            ->where(function ($query) use ($in_time, $out_time) {
                $query->where(function ($q) use ($in_time, $out_time) {
                    $q->where('in_time', '>=', $in_time)
                        ->where('in_time', '<', $out_time);
                })
                    ->orWhere(function ($q) use ($in_time, $out_time) {
                        $q->where('out_time', '>', $in_time)
                            ->where('out_time', '<=', $out_time);
                    })
                    ->orWhere(function ($q) use ($in_time, $out_time) {
                        $q->where('in_time', '<=', $in_time)
                            ->where('out_time', '>=', $out_time);
                    })
                    ->orWhere(function ($q) use ($in_time, $out_time) {
                        $q->where('in_time', '>=', $in_time)
                            ->where('out_time', '<=', $out_time);
                    });
            })
            ->exists();

        if ($attendance) {
            return false;
        } else {
            return true;
        }

        //raw codding

        //        $attendance = AttendanceDetail::where('attendance_id', $attendance->id)->orderBy('in_time','ASC')->get();
        //
        //        if (($in_time >= $attendance[count($attendance) - 1]->out_time) || ($out_time <= $attendance[0]->in_time)) {
        //            return true;
        //        }
        //        $is_status = false;
        //        foreach ($attendance as $current_value) {
        //            if (
        //                ($in_time < $current_value->in_time && $out_time < $current_value->out_time)
        //                || ($in_time > $current_value->in_time && $out_time > $current_value->out_time)
        //                || ($in_time >= $current_value->in_time && $out_time <= $current_value->out_time)
        //                || ($in_time < $current_value->in_time && $out_time > $current_value->out_time)
        //            ) {
        //                return false;
        //            } else {
        //                $is_status = true;
        //            }
        //        }
        //        if ($is_status == true) {
        //            return true;
        //        }

    }
}
