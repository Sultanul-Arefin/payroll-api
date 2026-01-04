<?php

namespace Modules\TimeManagement\Http\Controllers;

use App\Models\Bonus;
use App\Models\User;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Attendance\Entities\Attendance;
use Modules\TimeManagement\Http\Resources\AttendanceReportResource;
use Modules\TimeManagement\Http\Resources\CalendarOverviewResource;
use Modules\TimeManagement\Http\Resources\TimeManagementResource;

class TimeManagementController extends Controller
{
    // function time_management_report(Request $request)
    // {
    //     $request->validate([
    //         'department_id' => 'required|integer',
    //         'year' => 'required'
    //     ]);
    //     $users = User::query()
    //             ->where('company_id', auth()->user()->company_id)
    //             ->where('department_id', $request->department_id)
    //             ->where('status', User::USER_ACTIVE)
    //             ->get();
    //     $response = TimeManagementResource::collection(
    //         $users
    //     );
    //     return apiResponse(
    //         data: $response
    //     );
    // }

    function time_management_report(Request $request)
    {
        $request->validate([
            'department_id' => 'required',
            'year' => 'required'
        ]);

        $users = User::query()
            ->where('company_id', auth()->user()->company_id)
            ->where('status', User::USER_ACTIVE)
            ->when($request->department_id !== 'all', function ($query) use ($request) {
                $query->where('department_id', $request->department_id);
            })
            ->get();

        $response = TimeManagementResource::collection($users);

        return apiResponse(
            data: $response
        );
    }

    function attendance_report(Request $request)
    {
        $request->validate([
            'from_date' => 'required|date|date_format:Y-m-d',
            'to_date' => 'required|date|date_format:Y-m-d'
        ]);
        $users = User::query()
                ->where('company_id', auth()->user()->company_id)
                ->where('status', User::USER_ACTIVE)
                ->where('role_id', '!=', User::ADMIN)
                ->get();
        return AttendanceReportResource::collection(
            $users
        )->additional([
            'meta' => [
                'bonus_report' => $this->get_bonus_report($request->from_date, $request->to_date)
            ],
        ]);
    }

    public function get_bonus_report($from_date, $to_date){
        $bonus = Bonus::query()
                ->whereHas(
                    'user', function (Builder $builder) {
                        $builder
                            ->where('company_id', auth()->user()->company_id);
                    }
                )
                ->whereBetween(
                    'date',
                    [
                        $from_date,
                        $to_date
                    ]
                )
                ->get();
        return $bonus->map(function($q){
            $q->employee_name = $q?->user?->name;
            $q->department_name = $q->user->department?->department_name;
            $q->bonus_type = match($q->type){
                Bonus::BONUS_HOURLY => "Hourly",
                Bonus::BONUS_SALARY_BASIC => "Salary Basic",
                Bonus::BONUS_DIRECT_AMOUNT => "Direct Amount",
                Bonus::OVERTIME => "Overtime",
                Bonus::DOUBLE_OVERTIME => "Double Overtime",
                Bonus::RECUPERATED => "Recuperated",
            };
            $q->given_amount = $q->given_amount;
            $q->generalize_amount = $q->generalize_amount;
            $q->given_date = $q->date;
            $q->month = date('F', strtotime($q->date));

            unset($q->type, $q->created_at, $q->updated_at, $q->date, $q->user);

            return $q;
        });
    }

    public function delete_bonus(Bonus $bonus){
        $bonus->delete();

        return apiResponse(
            data: null,
            message: 'Bonus Successfully Deleted'
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
                    ->where('status', Attendance::PRESENT)
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
