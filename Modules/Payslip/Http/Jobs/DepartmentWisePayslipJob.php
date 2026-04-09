<?php

namespace Modules\Payslip\Http\Jobs;

use App\Models\Bonus;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Http\Request;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Modules\Attendance\Entities\Attendance;
use Modules\EmployeeSalaryItems\Entities\EmployeeSalaryItem;
use Modules\LeaveManagement\Entities\UserLeave;
use Modules\LeaveManagement\Entities\UserLeaveDetail;
use Modules\Payslip\Http\Controllers\PayslipController;
use Modules\SalaryItemsName\Entities\LeaveSalaryItems;
use Modules\User\Emails\SendPassword;

class DepartmentWisePayslipJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $auth_user;
    public $department_id;
    public $from_date;
    public $to_date;
    public $payment_date;

    /**
     * Create a new message instance.
     */
    public function __construct($auth_user, $department_id, $from_date, $to_date, $payment_date)
    {
        $this->auth_user = $auth_user;
        $this->department_id = $department_id;
        $this->from_date = $from_date;
        $this->to_date = $to_date;
        $this->payment_date = $payment_date;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Mail::to($this->email)
        //     ->send(new SendPassword($this->username, $this->password));
        $users = User::where('department_id', $this->department_id)->where('company_id', $this->auth_user->company_id)->where('status', User::USER_ACTIVE)->get();
        foreach($users as $user){

            $request = new Request([
                'employee_id' => $user->id,
                'from_date' => $this->from_date,
                'to_date' => $this->to_date,
                'payment_date' => $this->payment_date,
                'company_id' => $this->auth_user->company_id,
                'working_hours' => $this->get_hours_worked($user->id, request('from_date'), request('to_date')),
                'maternity_leave' => $this->getLeaveData($user->id, "maternity_leave"),
                'annual_leave' => $this->getLeaveData($user->id, "annual_leave"),
                'sick_leave' => $this->getLeaveData($user->id, "sick_leave"),
                'absent' => $this->getLeaveData($user->id, "absent"),
                'unpaid_sick_leave' => $this->getLeaveData($user->id, "unpaid_sick_leave"),
                'overtime' => $this->getLeaveData($user->id, "overtime"),
                'double_overtime' => $this->getLeaveData($user->id, "double_overtime"),
                'recuperated_hours' => $this->getLeaveData($user->id, "recuperated_hours"),
                'bonus' => $this->getLeaveData($user->id, "bonus")
            ]);
            app(PayslipController::class)->run_payslip($request);
            // dump($user->id);
        }
        // dump($this->auth_user->company_id);
        // dump([1,2,3,4]);
    }

    public function getLeaveData($employee_id, $leave)
    {
        $user = User::where('id', $employee_id)->first();
        $working_hours_per_day = $user->company?->working_hours_per_day;

        if($leave == "annual_leave")
        {
            $annual_leave_data = LeaveSalaryItems::query()
                ->whereHas(
                    'salary_items_name', function(Builder $builder) use($user){
                        $builder
                        ->where(
                            'name',
                            'Annual Leave'
                        )->where(
                            'company_id',
                            $user->company_id
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
                    'salary_items_name', function(Builder $builder)use($user){
                        $builder
                        ->where(
                            'name',
                            'Sick Leave'
                        )->where(
                            'company_id',
                            $user->company_id
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
                    'salary_items_name', function(Builder $builder)use($user){
                        $builder
                        ->where(
                            'name',
                            'Absent'
                        )->where(
                            'company_id',
                            $user->company_id
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
        elseif($leave == "unpaid_sick_leave"){
            $unpaid_sick_leave_data = LeaveSalaryItems::query()
                ->whereHas(
                    'salary_items_name', function(Builder $builder)use($user){
                        $builder
                        ->where(
                            'name',
                            'Unpaid Sick Leave'
                        )->where(
                            'company_id',
                            $user->company_id
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
            return $count;
        }
        elseif($leave == "double_overtime")
        {
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
            return $count;
        }
        elseif($leave == "recuperated_hours")
        {
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
            return $count;
        }
        elseif($leave == "bonus")
        {
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
            return $count;
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
