<?php

namespace Modules\TimeManagement\Http\Resources;

use App\Models\Overtime;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Attendance\Entities\Attendance;
use Modules\LeaveManagement\Entities\UserLeave;
use Modules\LeaveManagement\Entities\UserLeaveDetail;
use Modules\SalaryItemsName\Entities\LeaveSalaryItems;

class AttendanceReportResource extends JsonResource
{
    public function toArray($request)
    {
        
        return [
            'name' => $this->name,
            'department_name' => $this?->department?->department_name,
            'present_days' => $this->getPresentDays($this->id),
            'overtime' => $this->getOvertimes($this->id),
            'double_overtime' => $this->getDoubleOvertime($this->id),
            'early_departure' => $this->getEarlyDeparture($this->id),
            'annual_leave' => $this->getAnnualLeave($this->id),
            'sick_leave' => $this->getSickLeave($this->id),
            'unpaid_sick_leave_absent' => $this->getUnpaidSickLeave($this->id),
            'recuperated_hour' => $this->getRecuperatedHour($this->id),
        ];

        
    }
    
    function getPresentDays($user_id) {
        $present_days = Attendance::query()
                    ->where('user_id', $user_id)
                    ->where('status', Attendance::PRESENT)
                    ->whereBetween(
                        'dates',
                        [
                            request()->from_date,
                            request()->to_date
                        ]
                    )
                    ->count();
        return $present_days;
    }

    public function getOvertimes($user_id)
    {
        // Get working hours per day including lunch/others
        $working_hours_per_day = auth()->user()->company?->working_hours_per_day + auth()->user()->company?->lunch_and_others_per_day;

        if (!$working_hours_per_day) {
            return 0; // avoid division by zero
        }

        $attendanceIds = Attendance::query()
            ->where('user_id', $user_id)
            ->where('status', Attendance::PRESENT)
            ->whereBetween('dates', [
                request()->from_date,
                request()->to_date,
            ])
            ->pluck('id');

       // Step 2: Get total  overtime hours
       $TotalOvertimeHours = Overtime::whereIn('attendance_id', $attendanceIds)
       ->where('is_overtime', Overtime::OVERTIME)
       ->sum('hour');

        // Return as days (rounded to 2 decimal places)
        return round(floatval(($TotalOvertimeHours / $working_hours_per_day)),2);
    }


    
    public function getDoubleOvertime($user_id)
    {
        // Get working hours per day including lunch/others
        $working_hours_per_day = auth()->user()->company?->working_hours_per_day + auth()->user()->company?->lunch_and_others_per_day;

        if (!$working_hours_per_day) {
            return 0; // avoid division by zero
        }

        $attendanceIds = Attendance::query()
            ->where('user_id', $user_id)
            ->where('status', Attendance::PRESENT)
            ->whereBetween('dates', [
                request()->from_date,
                request()->to_date,
            ])
            ->pluck('id');

        // Sum total double overtime hours
        $totalDoubleOvertimeHours = Overtime::whereIn('attendance_id', $attendanceIds)
            ->where('is_overtime', Overtime::DOUBLE_OVERTIME)
            ->sum('hour');

        // Return as days (rounded to 2 decimal places)
        return round(floatval(($totalDoubleOvertimeHours / $working_hours_per_day)),2);
    }

    public function getEarlyDeparture($user_id)
    {
        // Get working hours per day including lunch/others
        $working_hours_per_day = auth()->user()->company?->working_hours_per_day + auth()->user()->company?->lunch_and_others_per_day;

        if (!$working_hours_per_day) {
            return 0; // avoid division by zero
        }

        $attendanceIds = Attendance::query()
            ->where('user_id', $user_id)
            ->where('status', Attendance::PRESENT)
            ->whereBetween('dates', [
                request()->from_date,
                request()->to_date,
            ])
            ->pluck('id');

       // Step 2: Get total earlyDeparture hours
       $totalEarlyDepartureHours = Overtime::whereIn('attendance_id', $attendanceIds)
       ->where('is_overtime', Overtime::EARLY_DAY_DEPARTURE)
       ->sum('hour');

        // Return as days (rounded to 2 decimal places)
        return round(floatval(($totalEarlyDepartureHours / $working_hours_per_day)),2);
    }
    

    function getAnnualLeave($user_id) {
       
        $annual_leave_data = LeaveSalaryItems::query()
            ->whereHas(
                'salary_items_name', function(Builder $builder){
                    $builder
                    ->where(
                        'name',
                        'Annual Leave'
                    )->where(
                        'company_id',
                        auth()->user()->company_id
                    );
                }
            )
            ->first();
        $data = UserLeave::query()
                ->where('user_id', $user_id)
                ->where('leave_type', $annual_leave_data->salary_items_id)
                ->where('status', UserLeave::APPROVED)
                ->get();
        $count = 0;
        foreach($data as $value){
            $details = UserLeaveDetail::query()
                ->where('user_leaves_id', $value->id)
                ->count();
            $count += $details;
        }
        return $count;
    }

    function getSickLeave($user_id) {
       
        $sick_leave_data = LeaveSalaryItems::query()
            ->whereHas(
                'salary_items_name', function(Builder $builder){
                    $builder
                    ->where(
                        'name',
                        'Sick Leave'
                    )->where(
                        'company_id',
                        auth()->user()->company_id
                    );
                }
            )
            ->first();
        $data = UserLeave::query()
                ->where('user_id', $user_id)
                ->where('leave_type', $sick_leave_data->salary_items_id)
                ->where('status', UserLeave::APPROVED)
                ->get();
        $count = 0;
        foreach($data as $value){
            $details = UserLeaveDetail::query()
                ->where('user_leaves_id', $value->id)
                ->count();
            $count += $details;
        }
        return $count;
    }

    function getUnpaidSickLeave($user_id) {
        
        $sick_leave_data = LeaveSalaryItems::query()
        ->whereHas(
            'salary_items_name', function(Builder $builder){
                $builder
                ->where(
                    'name',
                    'Unpaid Sick Leave'
                )->where(
                    'company_id',
                    auth()->user()->company_id
                );
            }
        )
        ->first();
    $data = UserLeave::query()
            ->where('user_id', $user_id)
            ->where('leave_type', $sick_leave_data->salary_items_id)
            ->where('status', UserLeave::APPROVED)
            ->get();
    $count = 0;
    foreach($data as $value){
        $details = UserLeaveDetail::query()
            ->where('user_leaves_id', $value->id)
            ->count();
        $count += $details;
    }
    return $count;
    }

    public function getRecuperatedHour($user_id)
    {
        // Get working hours per day including lunch/others
        $working_hours_per_day = auth()->user()->company?->working_hours_per_day + auth()->user()->company?->lunch_and_others_per_day;

        if (!$working_hours_per_day) {
            return 0; // avoid division by zero
        }

        $attendanceIds = Attendance::query()
            ->where('user_id', $user_id)
            ->where('status', Attendance::PRESENT)
            ->whereBetween('dates', [
                request()->from_date,
                request()->to_date,
            ])
            ->pluck('id');

       // Step 2: Get total Recuperated  hours
       $totalRecuperatedHour = Overtime::whereIn('attendance_id', $attendanceIds)
            ->where('is_overtime', Overtime::RECUPERATED)
            ->sum('hour');

        // Return as days (rounded to 2 decimal places)
        return round(floatval(($totalRecuperatedHour / $working_hours_per_day)),2);
    }


}
