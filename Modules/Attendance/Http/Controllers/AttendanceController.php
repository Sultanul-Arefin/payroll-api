<?php

namespace Modules\Attendance\Http\Controllers;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
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

    public function store(Request $request)
    {
        date_default_timezone_set('Asia/Dhaka');
        return apiResponse(
            data: [
                '1' => date_default_timezone_get(),
                '2' => Carbon::now()->timezoneName,
                '3' => config('app.timezone')
            ]
        );
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
         * we'll check for the web, & machine attendance
         * we'll insert, & show the value accordingly
         */
        $request->validate([
            'dates' => 'required',
            'in_time' => 'required',
            'out_time' => 'required',
            'office_type' => 'required'
        ]);

        // CHECK IF ATTENDANCE EXIST FOR THAT DAY
        $attendance = $this->attendanceService->checkIfAttendanceExist($request->dates);

        if ($attendance) {
            $checkIfSameTimeRangeAttendanceExist = $this->attendanceService->attendanceIsPossible($attendance, $request->in_time, $request->out_time);
            if ($checkIfSameTimeRangeAttendanceExist) {
                $attendance_details = AttendanceDetail::create([
                    'attendance_id' => $attendance->id,
                    'office_type' => $request->office_type,
                    'in_time' => $request->in_time,
                    'out_time' => $request->out_time,
                ]);

                // ATTENDANCE UPDATE NOTIFICATION
                $data = [
                    'title' => 'Existing Attendance Update',
                    'description' => "Attendance Given By: <b>" . auth()->user()->name . "</b>",
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
                'office_type' => $request->office_type,
                'in_time' => $request->in_time,
                'out_time' => $request->out_time,
            ]);

            // ATTENDANCE CREATION NOTIFICATION
            $data = [
                'title' => 'New Attendance Given',
                'description' => "Attendance Given By <b>" . auth()->user()->name . "</b>",
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

    public function update_attendance_details(AttendanceDetail $attendance_detail, Request $request)
    {
        $request->validate([
            'in_time' => 'required',
            'out_time' => 'required',
        ]);
        $attendance_detail->update([
            'in_time' => $request->in_time,
            'out_time' => $request->out_time,
        ]);
        return apiResponse(
            data: $attendance_detail,
        );
    }

    public function manual_attendance_by_admin(Request $request): JsonResponse
    {
        /**
         * we have to check for the machine, & web attendance
         * for the machine attendance, in_time & out_time will not come together
         * we'll check for the web, & machine attendance
         * we'll insert, & show the value accordingly
         */
        $request->validate([
            'dates' => 'required',
            'in_time' => 'required',
            'out_time' => 'required',
            'office_type' => 'required',
            'user_id' => 'required|exists:users,id'
        ]);
        $target_user = User::where('id', $request->user_id)->first();
        if ($target_user->user_details->attendance_type == UserDetails::MACHINE_ATTENDANCE) {
            return apiResponse(
                data: [
                    'message' => 'This user\'s attendance type is machine. Please update to web attendance to give attendance from here',
                ],
                message: 'This user\'s attendance type is machine. Please update to web attendance to give attendance from here',
                status: 'error',
                statusCode: 422
            );
        }


        // CHECK IF ATTENDANCE EXIST FOR THAT DAY
        $attendance = $this->attendanceService->checkIfAttendanceExistByAdmin($request->dates, $request->user_id);

        if ($attendance) {
            $checkIfSameTimeRangeAttendanceExist = $this->attendanceService->attendanceIsPossible($attendance, $request->in_time, $request->out_time);
            if ($checkIfSameTimeRangeAttendanceExist) {
                $attendance_details = AttendanceDetail::create([
                    'attendance_id' => $attendance->id,
                    'office_type' => $request->office_type,
                    'in_time' => $request->in_time,
                    'out_time' => $request->out_time,
                ]);

                // ATTENDANCE UPDATE NOTIFICATION
                $data = [
                    'title' => 'Existing Attendance Update',
                    'description' => "Your Attendance of {$request->dates} Updated By <b>" . auth()->user()->name . "</b>",
                    'action' => [
                        'name' => auth()->user()->name,
                        'email' => auth()->user()->email,
                        'phone' => auth()->user()->phone,
                    ],
                    'action_at' => date('Y-m-d H:i:s'),
                    'type' => 'Attendance Management',
                    'color' => '',
                ];
                // $user = app(ProjectController::class)->getAdminUser();
                $target_user->notify(new AttendanceNotification($data));

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
        $attendances = DB::transaction(function () use ($request, $target_user) {
            $attendance = Attendance::create([
                'dates' => $request->dates,
                'user_id' => $target_user->id,
                'status' => Attendance::PRESENT,
            ]);
            $attendance_details = AttendanceDetail::create([
                'attendance_id' => $attendance->id,
                'office_type' => $request->office_type,
                'in_time' => $request->in_time,
                'out_time' => $request->out_time,
            ]);

            // ATTENDANCE CREATION NOTIFICATION
            $data = [
                'title' => 'New Attendance Given',
                'description' => "Your Attendance of {$request->dates} Given By <b>" . auth()->user()->name . "</b>",
                'action' => [
                    'name' => auth()->user()->name,
                    'email' => auth()->user()->email,
                    'phone' => auth()->user()->phone,
                ],
                'action_at' => date('Y-m-d H:i:s'),
                'type' => 'Attendance Management',
                'color' => '',
            ];
            // $user = app(ProjectController::class)->getAdminUser();
            $target_user->notify(new AttendanceNotification($data));

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
                        ->whereHas(
                            'user', function(Builder $builder){
                                $builder->where('company_id', auth()->user()->company_id);
                            }
                        )
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

    public function approve_individual_attendance(Attendance $attendance)
    {
        $attendance->update([
            'status' => Attendance::PRESENT
        ]);

        return apiResponse(
            data: null,
            message: 'Attendance Successfully Approved',
            status: 'success'
        );
    }

    public function destroy(Attendance $attendance, Request $request)
    {
        $request->validate([
            '_method' => 'required'
        ]);
        $attendance->delete();
        return apiResponse(
            data: null,
            message: 'Attendance Successfully Deleted',
            status: 'success'
        );
    }
}
