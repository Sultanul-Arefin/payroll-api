<?php

namespace Modules\Attendance\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Modules\Attendance\Entities\Attendance;
use Modules\Attendance\Entities\AttendanceDetail;
use Modules\Attendance\Http\Resources\AttendanceResourceForAdmin;
use Modules\Attendance\Http\Resources\AttendanceResourceForUser;
use Modules\Attendance\Http\Services\AttendanceService;
use Modules\Attendance\Notifications\AttendanceNotification;
use Modules\Attendance\Repositories\Interfaces\AttendanceRepositoryInterface;
use Modules\ProjectManagement\Http\Controllers\ProjectController;
use Modules\User\Entities\UserDetails;

class AttendanceController extends Controller
{
    public function __construct(private AttendanceRepositoryInterface $attendanceRepo, private AttendanceService $attendanceService)
    {
    }

    public function index()
    {
        $rows = 15;
        if (request()?->has('rows')) {
            $rows = (int) request('rows');
        }

        return AttendanceResourceForUser::collection(
            $this->attendanceRepo->allWithSearch(
                ['*'],
                [],
                $rows
            )
        )->additional([
            'meta' => [
                'attendance_type_key' => auth()->user()->user_details?->attendance_type,
                'attendance_type_value' => auth()->user()->user_details?->attendance_type == UserDetails::WEB_ATTENDANCE ? 'web' : 'machine',
            ],
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return Renderable
     */
    public function store(Request $request)
    {
        if (auth()->user()->user_details->attendance_type == UserDetails::MACHINE_ATTENDANCE) {
            return apiResponse(
                data: [
                    'message' => 'You\'re not allowed to give web attendance! Please Contact with HR or Admin for changing your attendance to web!',
                ],
                message: 'You\'re not allowed to give web attendance! Please Contact with HR or Admin for changing your attendance to web!',
                status: 'error',
                statusCode: 422
            );
        }
        /**
         * we have to check for the machine, & web attendance
         * for the machine attendance, in_time & out_time will not come together
         * we'll check for the web, & machine attendane
         * we'll insert, & show the value accordingly
         */
        $request->validate([
            'dates' => 'required',
            'in_time' => 'required',
            'out_time' => 'required',
        ]);

        // CHECK IF ATTENDANCE EXIST FOR THAT DAY
        $attendance = $this->attendanceService->checkIfAttendanceExist($request->dates);

        if ($attendance) {
            $checkIfSameTimeRangeAttendanceExist = $this->attendanceService->attendanceIsPossible($attendance, $request->in_time, $request->out_time);
            if ($checkIfSameTimeRangeAttendanceExist) {
                $attendance_details = AttendanceDetail::create([
                    'attendance_id' => $attendance->id,
                    'in_time' => $request->in_time,
                    'out_time' => $request->out_time,
                ]);

                // ATTENDANCE UPDATE NOTIFICATION
                $data = [
                    'title' => 'Existing Attendance Update',
                    'description' => "Attendance Given",
                    'action' => [
                        'name' => auth()->user()->name,
                        'email' => auth()->user()->email,
                        'phone' => auth()->user()->phone,
                    ],
                    'action_at' => date('Y-m-d H:i:s'),
                    'type' => 'Attendance Management',
                    'color' => '',
                ];
                $user = app(ProjectController::class)->getAdminUser();
                $user->notify(new AttendanceNotification($data));

                return apiResponse(
                    data: [],
                    message: 'This Slot Successfully Added',
                    status: 'success'
                );

            } else {
                return apiResponse(
                    data: [],
                    message: 'This Time Slot Is Already Booked!',
                    status: 'warning',
                    statusCode: 422
                );
            }

        }

        // CREATE A NEW ATTENDANCE
        $attendances = DB::transaction(function () use ($request) {
            $attendance = Attendance::create([
                'dates' => $request->dates,
                'user_id' => auth()->user()->id,
                'status' => Attendance::PENDING,
            ]);
            $attendance_details = AttendanceDetail::create([
                'attendance_id' => $attendance->id,
                'in_time' => $request->in_time,
                'out_time' => $request->out_time,
            ]);

            // ATTENDANCE CREATION NOTIFICATION
            $data = [
                'title' => 'New Attendance Given',
                'description' => "Attendance Given",
                'action' => [
                    'name' => auth()->user()->name,
                    'email' => auth()->user()->email,
                    'phone' => auth()->user()->phone,
                ],
                'action_at' => date('Y-m-d H:i:s'),
                'type' => 'Attendance Management',
                'color' => '',
            ];
            $user = app(ProjectController::class)->getAdminUser();
            $user->notify(new AttendanceNotification($data));

            return $attendance;
        });

        return apiResponse(
            data: $attendances,
            message: 'Attendance Successfully Added',
            status: 'success'
        );
    }

    public function requested_attendance()
    {
        $current_date = Carbon::now();
        $current_month = $current_date->month;
        $current_year = $current_date->year;
        $requested_attendance = Attendance::query()
                        // ->whereYear('dates', $current_year)
                        // ->whereMonth('dates', $current_month)
            ->where('status', Attendance::PENDING)
            ->groupBy('dates')
            ->orderBy('dates')
            ->get();

        return AttendanceResourceForAdmin::collection($requested_attendance);
    }

    public function approve_all_attendance_by_date(Request $request)
    {
        $request->validate([
            'date' => 'required',
        ]);
        Attendance::where('dates', $request->date)->update([
            'status' => Attendance::PRESENT,
        ]);

        return apiResponse(
            data: null,
            message: 'Attendance Successfully Approved',
            status: 'success'
        );
    }
}
