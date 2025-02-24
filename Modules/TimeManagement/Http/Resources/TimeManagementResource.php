<?php

namespace Modules\TimeManagement\Http\Resources;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\LeaveManagement\Entities\UserLeave;
use Modules\LeaveManagement\Entities\UserLeaveDetail;
use Modules\SalaryItemsName\Entities\LeaveSalaryItems;

class TimeManagementResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'year' => date('Y'),
            'name' => $this->name,
            'employee_id' => 'EMP-ID',
            'department' => $this?->department?->department_name,
            'annual_leave_quota' => $this->getAnnualLeaveQuota($this->company),
            'annual_leave_taken' => $this->getAnnualLeaveTaken($this->id, Carbon::now()->year),
            'remaining_annual_leave' => $this->getAnnualLeaveQuota($this->company) - $this->getAnnualLeaveTaken($this->id, Carbon::now()->year),
            'sick_leave_quota' => $this->getSickLeaveQuota($this->company),
            'sick_leave_taken' => $this->getSickLeaveTaken($this->id, Carbon::now()->year),
            'remaining_sick_leave' => $this->getSickLeaveQuota($this->company) - $this->getSickLeaveTaken($this->id, Carbon::now()->year),
            // 'unpaid_sick_leave(absent)' => 0,
            'unpaid_sick_leave_absent' => 0,
            'maternity_leave' => 0
        ];
    }

    function getAnnualLeaveQuota($company): int {
        $data = LeaveSalaryItems::query()
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
        return $data->no_of_days;
    }

    function getAnnualLeaveTaken($user_id, $current_year): int {
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
                ->whereHas('leave_details', function(Builder $builder) use($current_year){
                    $builder->whereYear('dates', $current_year);
                })
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

    function getSickLeaveQuota($company): int {
        $data = LeaveSalaryItems::query()
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
        return $data->no_of_days;
    }

    function getSickLeaveTaken($user_id, $current_year): int {
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
                ->whereHas('leave_details', function(Builder $builder) use($current_year){
                    $builder->whereYear('dates', $current_year);
                })
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
}
