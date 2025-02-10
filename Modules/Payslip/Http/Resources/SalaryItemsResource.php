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
            'base' => $this->id // salary item id,
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
            if(request('maternity_leave') && $salary_item->salaryItemsName?->name == "Maternity Time Rate"){
                return (int)request('maternity_leave');
            }
            if(request('annual_leave') && $salary_item->salaryItemsName?->name == "Holiday Rate"){
                return (int)request('annual_leave');
            }
            if(request('sick_leave') && $salary_item->salaryItemsName?->name == "Paid Sick Leave Rate"){
                return (int)request('sick_leave');
            }
            if(request('unpaid_sick_leave') && $salary_item->salaryItemsName?->name == "Unpaid Sick Leave Rate"){
                return (int)request('unpaid_sick_leave');
            }
            if(request('absent') && $salary_item->salaryItemsName?->name == "Absent Rate"){
                return (int)request('absent');
            }
            if(request('overtime') && $salary_item->salaryItemsName?->name == "Overtime Rate"){
                return (int)request('overtime');
            }
            if(request('double_overtime') && $salary_item->salaryItemsName?->name == "Double Overtime Rate"){
                return (int)request('double_overtime');
            }
            if(request('bonus') && $salary_item->salaryItemsName?->name == "Bonus"){
                return (int)request('bonus');
            }
            if(request('recuperated_hours') && $salary_item->salaryItemsName?->name == "Recuperated Hour"){
                return (int)request('recuperated_hours');
            }
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
            return $salary_item->amount ? $salary_item->amount . " %" : null;
        }
        return $salary_item->amount;
    }

    public function getSalaryItemAmount($name)
    {
        return EmployeeSalaryItem::query()
                    ->where('employee_id', request('employee_id'))
                    ->whereHas(
                        'salaryItemsName', function (Builder $builder) use($name) {
                            $builder
                                ->where('name', $name)
                                ->where('salary_items_category_id', 1);
                        }
                    )
                    ->first('amount');
    }

    function getAmountCalculation($salary_item)
    {
        if($salary_item->salaryItemsName->name == "Wages"){
            if($salary_item->amount <= 0){
                $hourly_amount = $this->getSalaryItemAmount("Ordinary Time Rate");
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
            if(request('maternity_leave') && $salary_item->salaryItemsName?->name == "Maternity Time Rate"){
                return number_format((int)request('maternity_leave'), 2) * $this->getSalaryItemAmount("Maternity Time Rate")->amount;
            }
            if(request('annual_leave') && $salary_item->salaryItemsName?->name == "Holiday Rate"){
                return number_format((int)request('annual_leave'), 2) * $this->getSalaryItemAmount("Holiday Rate")->amount;
            }
            if(request('sick_leave') && $salary_item->salaryItemsName?->name == "Paid Sick Leave Rate"){
                return number_format((int)request('sick_leave'), 2) * $this->getSalaryItemAmount("Paid Sick Leave Rate")->amount;
            }
            if(request('unpaid_sick_leave') && $salary_item->salaryItemsName?->name == "Unpaid Sick Leave Rate"){
                return number_format((int)request('unpaid_sick_leave'), 2) * $this->getSalaryItemAmount("Unpaid Sick Leave Rate")->amount;
            }
            if(request('absent') && $salary_item->salaryItemsName?->name == "Absent Rate"){
                return number_format((int)request('absent'), 2) * $this->getSalaryItemAmount("Absent Rate")->amount;
            }
            if(request('overtime') && $salary_item->salaryItemsName?->name == "Overtime Rate"){
                return number_format((int)request('overtime'), 2) * $this->getSalaryItemAmount("Overtime Rate")->amount;
            }
            if(request('double_overtime') && $salary_item->salaryItemsName?->name == "Double Overtime Rate"){
                return number_format((int)request('double_overtime'), 2) * $this->getSalaryItemAmount("Double Overtime Rate")->amount;
            }
            if(request('bonus') && $salary_item->salaryItemsName?->name == "Bonus"){
                return number_format((int)request('bonus'), 2) * $this->getSalaryItemAmount("Bonus")->amount;
            }
            if(request('recuperated_hours') && $salary_item->salaryItemsName?->name == "Recuperated Hour"){
                return number_format((int)request('recuperated_hours'), 2) * $this->getSalaryItemAmount("Recuperated Hour")->amount;
            }
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
