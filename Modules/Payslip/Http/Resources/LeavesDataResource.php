<?php

namespace Modules\Payslip\Http\Resources;

use App\Models\Bonus;
use Carbon\Carbon;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Arr;
use JsonSerializable;
use Modules\Attendance\Entities\Attendance;
use Modules\EmployeeSalaryItems\Entities\EmployeeSalaryItem;
use Modules\LeaveManagement\Entities\UserLeave;
use Modules\LeaveManagement\Entities\UserLeaveDetail;
use Modules\SalaryItemsName\Entities\LeaveSalaryItems;

class LeavesDataResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  Request  $request
     * @return array|Arrayable|JsonSerializable
     */
    public function toArray($request)
    {
        return [
            'working_hours' => $this->get_hours_worked($this->id, request('from_date'), request('to_date')),
            'maternity_leave' => $this->getLeaveData($this->id, "maternity_leave"),
            'annual_leave' => $this->getLeaveData($this->id, "annual_leave"),
            'sick_leave' => $this->getLeaveData($this->id, "sick_leave"),
            'absent' => $this->getLeaveData($this->id, "absent"),
            'unpaid_sick_leave' => $this->getLeaveData($this->id, "unpaid_sick_leave"),
            'overtime' => $this->getLeaveData($this->id, "overtime"),
            'double_overtime' => $this->getLeaveData($this->id, "double_overtime"),
            'recuperated_hours' => $this->getLeaveData($this->id, "recuperated_hours"),
            'bonus' => $this->getLeaveData($this->id, "bonus")
        ];
    }

    public function getLeaveData($employee_id, $leave)
    {
        $user = auth()->user();
        $working_hours_per_day = $user->company?->working_hours_per_day;

        if($leave == "annual_leave")
        {
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
                    ->where('user_id', $employee_id)
                    ->where('leave_type', $annual_leave_data->salary_items_id)
                    ->where('status', UserLeave::APPROVED)
                    ->whereHas(
                        'leave_details', function(Builder $builder){
                            $builder->whereBetween(
                                'dates',
                                [
                                    request('from_date'),
                                    request('to_date')
                                ]
                            );
                        }
                    )
                    ->get();
            $count = 0;
            foreach($data as $value){
                $details = UserLeaveDetail::query()
                    ->where('user_leaves_id', $value->id)
                    ->count();
                $count += $details;
            }
            if($count <= 0){
                return null;
            }
            return $count * $working_hours_per_day;
        }
        elseif($leave == "sick_leave")
        {
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
                    ->where('user_id', $employee_id)
                    ->where('leave_type', $sick_leave_data->salary_items_id)
                    ->where('status', UserLeave::APPROVED)
                    ->whereHas(
                        'leave_details', function(Builder $builder){
                            $builder->whereBetween(
                                'dates',
                                [
                                    request('from_date'),
                                    request('to_date')
                                ]
                            );
                        }
                    )
                    ->get();
            $count = 0;
            foreach($data as $value){
                $details = UserLeaveDetail::query()
                    ->where('user_leaves_id', $value->id)
                    ->count();
                $count += $details;
            }
            if($count <= 0){
                return null;
            }
            return $count * $working_hours_per_day;
        }
        elseif($leave == "absent")
        {
            $absent_leave_data = LeaveSalaryItems::query()
                ->whereHas(
                    'salary_items_name', function(Builder $builder){
                        $builder
                        ->where(
                            'name',
                            'Absent'
                        )->where(
                            'company_id',
                            auth()->user()->company_id
                        );
                    }
                )
                ->first();
            $data = UserLeave::query()
                    ->where('user_id', $employee_id)
                    ->where('leave_type', $absent_leave_data->salary_items_id)
                    ->where('status', UserLeave::APPROVED)
                    ->whereHas(
                        'leave_details', function(Builder $builder){
                            $builder->whereBetween(
                                'dates',
                                [
                                    request('from_date'),
                                    request('to_date')
                                ]
                            );
                        }
                    )
                    ->get();
            $count = 0;
            foreach($data as $value){
                $details = UserLeaveDetail::query()
                    ->where('user_leaves_id', $value->id)
                    ->count();
                $count += $details;
            }
            if($count <= 0){
                return null;
            }
            return $count * $working_hours_per_day;
        }
        elseif($leave == "unpaid_sick_leave")
        {
            $unpaid_sick_leave_data = LeaveSalaryItems::query()
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
                    ->where('user_id', $employee_id)
                    ->where('leave_type', $unpaid_sick_leave_data->salary_items_id)
                    ->where('status', UserLeave::APPROVED)
                    ->whereHas(
                        'leave_details', function(Builder $builder){
                            $builder->whereBetween(
                                'dates',
                                [
                                    request('from_date'),
                                    request('to_date')
                                ]
                            );
                        }
                    )
                    ->get();
            $count = 0;
            foreach($data as $value){
                $details = UserLeaveDetail::query()
                    ->where('user_leaves_id', $value->id)
                    ->count();
                $count += $details;
            }
            if($count <= 0){
                return null;
            }
            return $count * $working_hours_per_day;
        }
        elseif($leave == "overtime")
        {
            $overtime_rate = EmployeeSalaryItem::query()
                    ->where('employee_id', $employee_id)
                    ->whereHas(
                        'salaryItemsName', function (Builder $builder) {
                            $builder
                                ->where('name', 'Overtime Rate')
                                ->where('salary_items_category_id', 1);
                        }
                    )
                    ->first('amount');
            $data = Bonus::query()
                    ->where('employee_id', $employee_id)
                    ->where('type', Bonus::OVERTIME)
                    ->whereBetween(
                        'date',
                        [
                            request('from_date'),
                            request('to_date')
                        ]
                    )
                    ->get();
            $count = 0;
            foreach($data as $value){
                $count += $value->generalize_amount;
            }
            if($count <= 0){
                return null;
            }
            return $count * $overtime_rate->amount;
        }
        elseif($leave == "double_overtime")
        {
            $double_overtime_rate = EmployeeSalaryItem::query()
                    ->where('employee_id', $employee_id)
                    ->whereHas(
                        'salaryItemsName', function (Builder $builder) {
                            $builder
                                ->where('name', 'Double Overtime Rate')
                                ->where('salary_items_category_id', 1);
                        }
                    )
                    ->first('amount');
            $data = Bonus::query()
                    ->where('employee_id', $employee_id)
                    ->where('type', Bonus::DOUBLE_OVERTIME)
                    ->whereBetween(
                        'date',
                        [
                            request('from_date'),
                            request('to_date')
                        ]
                    )
                    ->get();
            $count = 0;
            foreach($data as $value){
                $count += $value->generalize_amount;
            }
            if($count <= 0){
                return null;
            }
            return $count * $double_overtime_rate->amount;
        }
        elseif($leave == "recuperated_hours")
        {
            $recuperated_hour_rate = EmployeeSalaryItem::query()
                    ->where('employee_id', $employee_id)
                    ->whereHas(
                        'salaryItemsName', function (Builder $builder) {
                            $builder
                                ->where('name', 'Recuperated Hour')
                                ->where('salary_items_category_id', 1);
                        }
                    )
                    ->first('amount');
            $data = Bonus::query()
                    ->where('employee_id', $employee_id)
                    ->where('type', Bonus::RECUPERATED)
                    ->whereBetween(
                        'date',
                        [
                            request('from_date'),
                            request('to_date')
                        ]
                    )
                    ->get();
            $count = 0;
            foreach($data as $value){
                $count += $value->generalize_amount;
            }
            if($count <= 0){
                return null;
            }
            return $count * $recuperated_hour_rate->amount;
        }
        elseif($leave == "bonus")
        {
            $bonus_rate = EmployeeSalaryItem::query()
                    ->where('employee_id', $employee_id)
                    ->whereHas(
                        'salaryItemsName', function (Builder $builder) {
                            $builder
                                ->where('name', 'Bonus')
                                ->where('salary_items_category_id', 1);
                        }
                    )
                    ->first('amount');
            $data = Bonus::query()
                    ->where('employee_id', $employee_id)
                    ->whereIn('type', [Bonus::BONUS_HOURLY, Bonus::BONUS_SALARY_BASIC, Bonus::BONUS_DIRECT_AMOUNT])
                    ->whereBetween(
                        'date',
                        [
                            request('from_date'),
                            request('to_date')
                        ]
                    )
                    ->get();
            $count = 0;
            foreach($data as $value){
                $count += $value->generalize_amount;
            }
            if($count <= 0){
                return null;
            }
            return $count * $bonus_rate->amount;
        }
    }

    public function get_hours_worked($employee_id, $from_date, $to_date)
    {
        $attendances = Attendance::query()
                        ->where('user_id', $employee_id)
                        ->where('status', Attendance::PRESENT)
                        ->whereBetween(
                            'dates',
                            [
                                $from_date,
                                $to_date
                            ]
                        )
                        ->get();
        $total_minutes = 0;
        foreach($attendances as $value)
        {
            $details = $value->attendance_details;

            foreach($details as $detail)
            {
                $inTime = Carbon::parse($detail->in_time);
                $outTime = Carbon::parse($detail->out_time);

                // Calculate the time difference in minutes and add it to the total
                $timeDifferenceMinutes = $inTime->diffInHours($outTime); // have to check this code twice, there might be an issue in the inTime, outTime alignment
                // previous alignment
                // $timeDifferenceMinutes = $outTime->diffInHours($inTime);
                $total_minutes += $timeDifferenceMinutes;
            }
        }
        return $total_minutes;
    }
}
