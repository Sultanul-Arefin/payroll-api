<?php

namespace Modules\TimeManagement\Http\Resources;

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
            'overtime' => $this->getOvertime(),
            'double_overtime' => $this->getDoubleOvertime(),
            'early_departure' => $this->getEarlyDeparture(),
            'annual_leave' => $this->getAnnualLeave($this->id),
            'sick_leave' => $this->getSickLeave($this->id),
            'unpaid_sick_leave_absent' => $this->getUnpaidSickLeave(),
            'recuperated_hour' => $this->getRecuperatedHour()
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

    function getOvertime() {
        return 0;
    }

    function getDoubleOvertime() {
        return 0;
    }

    function getEarlyDeparture() {
        return 0;
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

    function getUnpaidSickLeave() {
        return 0;
    }

    function getRecuperatedHour() {
        return 0;
    }
}
