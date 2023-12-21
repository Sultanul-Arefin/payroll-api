<?php

namespace Modules\TimeManagement\Http\Controllers;

use App\Models\User;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Attendance\Entities\Attendance;
use Modules\TimeManagement\Http\Resources\AttendanceReportResource;
use Modules\TimeManagement\Http\Resources\CalendarOverviewResource;
use Modules\TimeManagement\Http\Resources\TimeManagementResource;

class TimeManagementController extends Controller
{
    function time_management_report() 
    {
        $users = User::where('company_id', auth()->user()->id)->where('status', User::USER_ACTIVE)->get();
        $response = TimeManagementResource::collection(
            $users
        );
        return apiResponse(
            data: $response
        );
    }

    function attendance_report() 
    {
        $users = User::where('company_id', auth()->user()->id)->where('status', User::USER_ACTIVE)->get();
        $response = AttendanceReportResource::collection(
            $users
        );
        return apiResponse(
            data: $response
        );
    }
    
    function attendance_calendar_overview_per_person(Request $request)
    {
        $request->validate([
            'department_id' => 'required',
            'user_id' => 'required',
            'from_date' => 'required|date|date_format:Y-m-d',
            'to_date' => 'required|date|date_format:Y-m-d'
        ]);
        $attendance = Attendance::query()
                    ->where('user_id', $request->user_id)
                    ->whereBetween(
                        'dates',
                        [
                            $request->from_date,
                            $request->to_date
                        ]
                    )
                    ->get();
        $response = CalendarOverviewResource::collection(
            $attendance
        );
        return apiResponse(
            data: $response
        );
    }
}
