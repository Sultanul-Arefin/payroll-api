<?php

namespace Modules\Payslip\Http\Resources;

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

class SalaryItemsResource extends JsonResource
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
            'name' => $this->salaryItemsName?->name,
            'hours_days' => $this->getHoursDaysCalculation($this),
            'rate' => $this->getRateCalculation($this, $this->is_percentage),
            'amount' => $this->getAmountCalculation($this),
            'base' => $this->id // salary item id
        ];
    }

    function getHoursDaysCalculation($salary_item) {
        if($salary_item->salaryItemsName->name == "Wages"){
            if($salary_item->amount <= 0){
                // $hours_worked = $this->get_hours_worked(request('employee_id'), request('from_date'), request('to_date'));
                $hours_worked = (int)request('working_hours');
                return round($hours_worked, 2) . " hours";
            }
            return "1 month";
        }
        if($salary_item->salaryItemsName->salaryItemsCategory->id == 1)
        {
            return 0; // this will come from no. of leave days
        }
        if($salary_item->salaryItemsName->salaryItemsCategory->id == 2)
        {
            $leave = $this->get_leave_details(request('employee_id'), $salary_item->salaryItemsName->id);
            return $leave;
        }
        return "1 month";
    }

    function getRateCalculation($salary_item, $is_percentage)
    {
        // for hourly pay
        if($salary_item->salaryItemsName->name == "Wages"){
            if($salary_item->amount <= 0){
                $hourly_amount = EmployeeSalaryItem::query()
                    ->where('employee_id', request('employee_id'))
                    ->whereHas(
                        'salaryItemsName', function (Builder $builder) {
                            $builder
                                ->where('name', 'Ordinary Time Rate')
                                ->where('salary_items_category_id', 1);
                        }
                    )
                    ->first('amount');
                return $hourly_amount->amount;
            }
        }

        if($salary_item->salaryItemsName->salaryItemsCategory->id == 7 || $salary_item->salaryItemsName->salaryItemsCategory->id == 8)
        {
            return $salary_item->amount;
        }
        if($is_percentage==1){
            return $salary_item->amount . " %";
        }
        return $salary_item->amount;
    }

    function getAmountCalculation($salary_item)
    {
        if($salary_item->salaryItemsName->name == "Wages"){
            if($salary_item->amount <= 0){
                $hourly_amount = EmployeeSalaryItem::query()
                    ->where('employee_id', request('employee_id'))
                    ->whereHas(
                        'salaryItemsName', function (Builder $builder) {
                            $builder
                                ->where('name', 'Ordinary Time Rate')
                                ->where('salary_items_category_id', 1);
                        }
                    )
                    ->first('amount');
                $hours_worked = (int)request('working_hours');
                // $hours_worked = $this->get_hours_worked(request('employee_id'), request('from_date'), request('to_date'));
                if($hours_worked < 0){
                    $employee_associated_amount = 0;
                } else{
                    $employee_associated_amount = $hourly_amount->amount * $hours_worked;
                }
                return round($employee_associated_amount, 2);
            }
            return round($salary_item->amount, 2);
        }
        if($salary_item->salaryItemsName->salaryItemsCategory->id == 1){
            return 0;
        }
        if($salary_item->salaryItemsName->salaryItemsCategory->id == 2){
            return round($salary_item->amount * $this->get_leave_details(request('employee_id'), $salary_item->salaryItemsName->id), 2); // * no of leave days/hours
        }
        if($salary_item->salaryItemsName->salaryItemsCategory->id == 7 || $salary_item->salaryItemsName->salaryItemsCategory->id == 8){
            if($salary_item->is_percentage == 1)
            {
                return 'Emp: ' . round($salary_item->deduction_details?->employee_amount, 2) . '%, Cmp_Or_Othrs: ' . round($salary_item->deduction_details?->government_or_company_amount, 2) . '%';
            }
            return 'Emp: ' . round($salary_item->deduction_details?->employee_amount, 2) . ', Cmp_Or_Othrs: ' . round($salary_item->deduction_details?->government_or_company_amount, 2);
        }
        if($salary_item->is_percentage == 1){
            return round($salary_item->amount / 100, 2);
        }
        return round($salary_item->amount, 2);
    }

    public function get_leave_details($employee_id, $item_id): int
    {
        $leave = UserLeave::query()
            ->where('user_id', $employee_id)
            ->where('status', UserLeave::APPROVED)
            ->where('leave_type', $item_id)
            ->get();
        $count = 0;
        foreach($leave as $value)
        {
            $count = $value?->leave_details?->count();
        }
        return $count;
    }

    // hours worked for employee
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
