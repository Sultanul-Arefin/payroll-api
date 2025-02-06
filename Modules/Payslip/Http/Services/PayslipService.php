<?php

namespace Modules\Payslip\Http\Services;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;
use Modules\Attendance\Entities\Attendance;
use Modules\Company\Entities\Company;
use Modules\EmployeeSalaryItems\Entities\DeductionDetails;
use Modules\EmployeeSalaryItems\Entities\EmployeeSalaryItem;
use Modules\LeaveManagement\Entities\UserLeave;
use Modules\LeaveManagement\Entities\UserLeaveDetail;
use Modules\Payslip\Entities\Payslip;
use Modules\Payslip\Entities\PayslipDetail;
use Modules\Payslip\Entities\PayslipDetailsForDeduction;
use Modules\SalaryItemsName\Entities\LeaveSalaryItems;

class PayslipService
{
    public function getCategoryOneData($employee_id)
    {
        $data = EmployeeSalaryItem::query()
            ->where('company_id', auth()->user()->company_id)
            ->where('employee_id', $employee_id)
            ->whereHas(
                'salaryItemsName', function (Builder $query) {
                    $query->where('salary_items_category_id', 1);
                }
            )
            ->get();

        return $data;
    }

    // function get_total_amount_except_basic_attendance($employee_id) {
    //     $employee_associated_amount = EmployeeSalaryItem::query()
    //                                 ->where('employee_id', $employee_id)
    //                                 ->whereHas(
    //                                     'salaryItemsName', function(Builder $builder){
    //                                         $builder->whereNotIn('salary_items_category_id', [1,2]);
    //                                     }
    //                                 )
    //                                 ->sum('amount');
    //     return $employee_associated_amount;
    // }

    public function add_payslip_details($payslip_id, $employee_id, $company_id, $from_date, $to_date)
    {
        $employee_associated_amount = EmployeeSalaryItem::query()
                                    // ->whereHas(
                                    //     'salaryItemsName', function(Builder $builder){
                                    //         $builder->whereNotIn('salary_items_category_id', [1,2]);
                                    //     }
                                    // )
            ->whereNotNull('amount')
            ->where('company_id', $company_id)
            ->where(function ($query) use($employee_id){
                $query->where('employee_id', $employee_id)
                        ->orWhere(function ($query) {
                            $query->whereNull('employee_id')
                                ->where('is_general', 1);
                        });
            })
            ->get();
        foreach ($employee_associated_amount as $value) {
            if($this->getAmountCalculation($value, $employee_id, $from_date, $to_date) != 0){
            // if($this->getAmountCalculation($value) >= 0){
                PayslipDetail::create([
                    'payslip_id' => $payslip_id,
                    'salary_item_id' => $value->salary_item_id,
                    'employee_salary_item_id' => $value->id,
                    'base_amount_or_hours' => $this->getBaseAmountOrHours($value, $payslip_id, $from_date, $to_date),
                    'rate' => $this->getRate($value, $employee_id),
                    'amount' => $this->getAmountCalculation($value, $employee_id, $from_date, $to_date),
                ]);
            }
        }

        // FOR DEDUCTION
        $salary_deduction_values = EmployeeSalaryItem::query()
                            ->whereHas(
                                'salaryItemsName', function(Builder $builder){
                                    $builder->whereIn('salary_items_category_id', [7,8]);
                                }
                            )
                            ->where('company_id', $company_id)
                            ->where(function ($query) use($employee_id){
                                $query->where('employee_id', $employee_id)
                                      ->orWhere(function ($query) {
                                          $query->whereNull('employee_id')
                                                ->where('is_general', 1);
                                      });
                            })
                            ->whereNull('amount')
                            ->get();
        foreach($salary_deduction_values as $value){
            if($value->salaryItemsName->salaryItemsCategory->id == 7){
                $deduction_value = DeductionDetails::query()
                    ->whereHas(
                        'employee_salary_item', function (Builder $builder) use ($employee_id, $company_id, $value) {
                            $builder
                                ->where('company_id', $company_id)
                                ->where(function ($query) use($employee_id){
                                    $query->where('employee_id', $employee_id)
                                          ->orWhere(function ($query) {
                                              $query->whereNull('employee_id')
                                                    ->where('is_general', 1);
                                          });
                                })
                                ->where('id', $value->id)
                                ->whereHas(
                                    'salaryItemsName', function (Builder $builder){
                                        $builder->whereHas(
                                            'salaryItemsCategory', function (Builder $builder){
                                                $builder->where('id', 7);
                                            }
                                        );
                                    }
                                );
                        }
                    )
                    ->get();
                    // ->sum('government_or_company_amount');
                foreach($deduction_value as $dv){
                    PayslipDetailsForDeduction::create([
                        'payslip_id' => $payslip_id,
                        'salary_item_id' => $dv->employee_salary_item?->salary_item_id,
                        'employee_amount' => $this->payslipDetailsForDeductionAmount($payslip_id, $value->is_percentage, $dv->employee_amount),
                        'government_or_company_amount' => $this->payslipDetailsForDeductionAmount($payslip_id, $value->is_percentage, $dv->government_or_company_amount),
                        'employee_amount_rate' => $value->is_percentage == 1 ? $dv->employee_amount . " %" : $dv->employee_amount,
                        'government_or_company_amount_rate' => $value->is_percentage == 1 ? $dv->government_or_company_amount . " %" : $dv->government_or_company_amount,
                    ]);
                }
            }
            if($value->salaryItemsName->salaryItemsCategory->id == 8){
                $deduction_value = DeductionDetails::query()
                    ->whereHas(
                        'employee_salary_item', function (Builder $builder) use ($employee_id, $company_id, $value) {
                            $builder
                                ->where('company_id', $company_id)
                                ->where(function ($query) use($employee_id){
                                    $query->where('employee_id', $employee_id)
                                          ->orWhere(function ($query) {
                                              $query->whereNull('employee_id')
                                                    ->where('is_general', 1);
                                          });
                                })
                                ->where('id', $value->id)
                                ->whereHas(
                                    'salaryItemsName', function (Builder $builder){
                                        $builder->whereHas(
                                            'salaryItemsCategory', function (Builder $builder){
                                                $builder->where('id', 8);
                                            }
                                        );
                                    }
                                );
                        }
                    )
                    ->get();
                    // ->sum('government_or_company_amount');
                foreach($deduction_value as $dv){
                    PayslipDetailsForDeduction::create([
                        'payslip_id' => $payslip_id,
                        'salary_item_id' => $dv->employee_salary_item?->salary_item_id,
                        'employee_amount' => $this->payslipDetailsForDeductionAmount($payslip_id, $value->is_percentage, $dv->employee_amount),
                        // 'employee_amount' => $dv->employee_amount,
                        'government_or_company_amount' => $this->payslipDetailsForDeductionAmount($payslip_id, $value->is_percentage, $dv->government_or_company_amount),
                        'employee_amount_rate' => $value->is_percentage == 1 ? $dv->employee_amount . " %" : $dv->employee_amount,
                        'government_or_company_amount_rate' => $value->is_percentage == 1 ? $dv->government_or_company_amount . " %" : $dv->government_or_company_amount,
                    ]);
                }
            }
        }
    }

    public function payslipDetailsForDeductionAmount($payslip_id, $is_percentage, $amount)
    {
        $payslip = Payslip::where('id', $payslip_id)->first();
        $taxable_gross_pay = $payslip->gross_pay_before_tax;

        if($is_percentage == 1){
            return round($taxable_gross_pay * ($amount / 100), 2);
        }
        return $amount;
    }

    public function getRate($salary_item, $employee_id)
    {
        if($salary_item->salaryItemsName->name == "Wages"){
            if($salary_item->amount <= 0){
                $hourly_amount = EmployeeSalaryItem::query()
                    ->where('employee_id', $employee_id)
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
        if($salary_item->salaryItemsName->salaryItemsCategory->id == 1)
        {
            $value = EmployeeSalaryItem::query()
                    ->where('employee_id', $employee_id)
                    ->whereHas(
                        'salaryItemsName', function (Builder $builder) use($salary_item) {
                            $builder
                                ->where('name', $salary_item->salaryItemsName->name)
                                ->where('salary_items_category_id', 1);
                        }
                    )
                    ->first('amount');
            return $value->amount;
        }
        if($salary_item->salaryItemsName->salary_items_category_id == 5 && $salary_item->salaryItemsName->is_threshold == 2){
            return "Threshold";
        }
        if($salary_item->salaryItemsName->salary_items_category_id == 5 && $salary_item->salaryItemsName->is_threshold == 1){
            if($salary_item->is_percentage == 1)
            {
                return $salary_item->amount . " %";
            }
            return $salary_item->amount;
        }
        if($salary_item->salaryItemsName->salary_items_category_id == 6){
            if($salary_item->is_percentage == 1){
                return $salary_item->amount . " %";
            }
            return $salary_item->amount;
        }
        return $salary_item->amount;
    }

    function getBaseAmountOrHours($salary_item, $payslip_id, $from_date, $to_date)
    {
        $payslip = Payslip::where('id', $payslip_id)->first();
        $user = User::where('id', $payslip->employee_id)->first();

        if($salary_item->salaryItemsName->name == "Wages"){
            if($salary_item->amount <= 0){
                if(request('working_hours')){
                    return (int)request('working_hours') . " hours";
                } else{
                    return (int)$this->get_hours_worked($user->id, $from_date, $to_date) . " hours";
                }
            }
        }
        if(request('maternity_leave') && $salary_item->salaryItemsName?->name == "Maternity Time Rate"){
            return (int)request('maternity_leave') . " hours";
        }
        if(request('annual_leave') && $salary_item->salaryItemsName?->name == "Holiday Rate"){
            return (int)request('annual_leave') . " hours";
        } elseif($salary_item->salaryItemsName?->name == "Holiday Rate"){
            $total = $this->getLeaveData($user->id, "annual_leave", $from_date, $to_date);

            if($total > 0){
                return $total . " hours";
            }
        }
        if(request('sick_leave') && $salary_item->salaryItemsName?->name == "Paid Sick Leave Rate"){
            return (int)request('sick_leave') . " hours";
        } elseif($salary_item->salaryItemsName?->name == "Paid Sick Leave Rate"){
            $total = $this->getLeaveData($user->id, "sick_leave", $from_date, $to_date);

            if($total > 0){
                return $total . " hours";
            }
        }
        if(request('unpaid_sick_leave') && $salary_item->salaryItemsName?->name == "Unpaid Sick Leave"){
            return (int)request('unpaid_sick_leave') . " hours";
        } elseif($salary_item->salaryItemsName?->name == "Unpaid Sick Leave"){
            $total = $this->getLeaveData($user->id, "unpaid_sick_leave", $from_date, $to_date);
            // $total = $this->getLeaveData($employee_id, "absent", $from_date, $to_date) * $this->getSalaryItemAmount("Absent Rate", $employee_id)->amount;

            if($total > 0){
                return $total . " hours";
            }
        }
        if(request('absent') && $salary_item->salaryItemsName?->name == "Absent"){
            return (int)request('absent') . " hours";
        } elseif($salary_item->salaryItemsName?->name == "Absent"){
            $total = $this->getLeaveData($user->id, "absent", $from_date, $to_date);
            // $total = $this->getLeaveData($employee_id, "absent", $from_date, $to_date) * $this->getSalaryItemAmount("Absent Rate", $employee_id)->amount;

            if($total > 0){
                return $total . " hours";
            }
        }
        if(request('overtime') && $salary_item->salaryItemsName?->name == "Overtime Rate"){
            return (int)request('overtime') . " hours";
        }
        if(request('double_overtime') && $salary_item->salaryItemsName?->name == "Double Overtime Rate"){
            return (int)request('double_overtime') . " hours";
        }
        if(request('bonus') && $salary_item->salaryItemsName?->name == "Bonus"){
            return (int)request('bonus') . " hours";
        }
        if(request('recuperated_hours') && $salary_item->salaryItemsName?->name == "Recuperated Hour"){
            return (int)request('recuperated_hours') . " hours";
        }
        if($salary_item->salaryItemsName->salary_items_category_id == 5 && $salary_item->salaryItemsName->is_threshold == 2){
            $payslip = Payslip::where('id', $payslip_id)->first();
            return ((($payslip->wages + $payslip->additional_pay) - $payslip->leave_deduction) + $payslip->taxable_allowance + $payslip->non_taxable_allowance) - $payslip->non_taxable_allowance;
        }
        if($salary_item->salaryItemsName->salary_items_category_id == 5 && $salary_item->salaryItemsName->is_threshold == 1){
            $payslip = Payslip::where('id', $payslip_id)->first();
            return ((($payslip->wages + $payslip->additional_pay) - $payslip->leave_deduction) + $payslip->taxable_allowance + $payslip->non_taxable_allowance) - $payslip->non_taxable_allowance;
        }
        return "1 month";
    }

    function getAmountCalculation($salary_item, $employee_id, $from_date, $to_date)
    {
        $user = User::where('id', $employee_id)->first();
        $company = Company::where('id', $user->company_id)->first();
        $working_hour_per_day = $company->working_hours_per_day;

        if($salary_item->salaryItemsName->name == "Wages"){
            if($salary_item->amount <= 0){
                if(request('working_hours')){
                    $hours_worked = (int)request('working_hours');
                } else{
                    $hours_worked = (int)$this->get_hours_worked($user->id, $from_date, $to_date);
                }
                $hourly_amount = EmployeeSalaryItem::query()
                    ->where('employee_id', $employee_id)
                    ->whereHas(
                        'salaryItemsName', function (Builder $builder) {
                            $builder
                                ->where('name', 'Ordinary Time Rate')
                                ->where('salary_items_category_id', 1);
                        }
                    )
                    ->first('amount');
                if($hours_worked < 0){
                    $employee_associated_amount = 0;
                } else{
                    $employee_associated_amount = $hourly_amount->amount * $hours_worked;
                }
                return round($employee_associated_amount, 2);
                // return $this->get_hours_worked(request('employee_id'), request('from_date'), request('to_date'));
            }
            return $salary_item->amount;
        }
        if($salary_item->salaryItemsName->salaryItemsCategory->id == 1){
            if(request('maternity_leave') && $salary_item->salaryItemsName?->name == "Maternity Time Rate"){
                return (int)request('maternity_leave') * $this->getSalaryItemAmount("Maternity Time Rate", $employee_id)->amount;
                // return 0;
            }
            if(request('annual_leave') && $salary_item->salaryItemsName?->name == "Holiday Rate"){
                return (int)request('annual_leave') * $this->getSalaryItemAmount("Holiday Rate", $employee_id)->amount;
                // return 0;
            } elseif($salary_item->salaryItemsName?->name == "Holiday Rate"){
                $total = $this->getLeaveData($employee_id, "annual_leave", $from_date, $to_date);

                if($total > 0){
                    return $total * $this->getSalaryItemAmount("Holiday Rate", $employee_id)->amount;
                }
                return 0;
            }
            if(request('sick_leave') && $salary_item->salaryItemsName?->name == "Paid Sick Leave Rate"){
                return (int)request('sick_leave') * $this->getSalaryItemAmount("Paid Sick Leave Rate", $employee_id)->amount;
                // return 0;
            } elseif($salary_item->salaryItemsName?->name == "Paid Sick Leave Rate"){
                $total = $this->getLeaveData($employee_id, "sick_leave", $from_date, $to_date);

                if($total > 0){
                    return $total * $this->getSalaryItemAmount("Paid Sick Leave Rate", $employee_id)->amount;
                }
                return 0;
            }

            if(request('overtime') && $salary_item->salaryItemsName?->name == "Overtime Rate"){
                return (int)request('overtime') * $this->getSalaryItemAmount("Overtime Rate", $employee_id)->amount;
            }
            if(request('double_overtime') && $salary_item->salaryItemsName?->name == "Double Overtime Rate"){
                return (int)request('double_overtime') * $this->getSalaryItemAmount("Double Overtime Rate", $employee_id)->amount;
            }
            if(request('bonus') && $salary_item->salaryItemsName?->name == "Bonus"){
                return (int)request('bonus') * $this->getSalaryItemAmount("Bonus", $employee_id)->amount;
            }
            if(request('recuperated_hours') && $salary_item->salaryItemsName?->name == "Recuperated Hour"){
                return (int)request('recuperated_hours') * $this->getSalaryItemAmount("Recuperated Hour", $employee_id)->amount;
                // return 0;
            }
            return 0;
        }
        if($salary_item->salaryItemsName->salaryItemsCategory->id == 2){
            if(request('unpaid_sick_leave') && $salary_item->salaryItemsName?->name == "Unpaid Sick Leave"){
                return (int)request('unpaid_sick_leave') * $this->getSalaryItemAmount("Unpaid Sick Leave Rate", $employee_id)->amount;
            } elseif($salary_item->salaryItemsName?->name == "Unpaid Sick Leave"){
                $total = $this->getLeaveData($employee_id, "unpaid_sick_leave", $from_date, $to_date);
                // $total = $this->getLeaveData($employee_id, "absent", $from_date, $to_date) * $this->getSalaryItemAmount("Absent Rate", $employee_id)->amount;

                if($total > 0){
                    return $total * $this->getSalaryItemAmount("Unpaid Sick Leave Rate", $employee_id)->amount;
                }
                return 0;
            }
            if(request('absent') && $salary_item->salaryItemsName?->name == "Absent"){
                return (int)request('absent') * $this->getSalaryItemAmount("Absent Rate", $employee_id)->amount;
            } elseif($salary_item->salaryItemsName?->name == "Absent"){
                $total = $this->getLeaveData($employee_id, "absent", $from_date, $to_date);
                // $total = $this->getLeaveData($employee_id, "absent", $from_date, $to_date) * $this->getSalaryItemAmount("Absent Rate", $employee_id)->amount;

                if($total > 0){
                    return $total * $this->getSalaryItemAmount("Absent Rate", $employee_id)->amount;
                }
                return 0;
            }
            return $salary_item->amount * $this->get_leave_details(request('employee_id'), $salary_item->salaryItemsName->id); // * no of leave days/hours
        }
        if($salary_item->salaryItemsName->salaryItemsCategory->id == 5){
            // $categoryOneAmount = $this->getCategoryIdOneAmount(1, $salary_item->company_id, $salary_item->employee_id);
            $gross_pay_before_tax = ($this->getAmountForEmployee(1, $salary_item->employee_id, $from_date, $to_date) - $this->getAmountForEmployee(2, $salary_item->employee_id, $from_date, $to_date)) + $this->getAmountForEmployee(3, $salary_item->employee_id, $from_date, $to_date);

            if($salary_item->salaryItemsName->salary_items_category_id == 5 && $salary_item->salaryItemsName->is_threshold == 2){

                $threshold_value = 0;
                $percentage_amount = $salary_item->amount; // get the percentage value
                $threshold_details = $salary_item->threshold_details; // get threshold details to check if the wages is between the details
                if($threshold_details)
                {
                    $start_percentage_after = $threshold_details->start_percentage_after;
                    $end_percentage_at = $threshold_details->end_percentage_at;

                    if ($start_percentage_after <= $gross_pay_before_tax && (is_null($end_percentage_at) || $end_percentage_at >= $gross_pay_before_tax)) {
                        $threshold_value += ($gross_pay_before_tax * ($percentage_amount / 100));
                    }
                }
                return $threshold_value;
            }
            if($salary_item->salaryItemsName->salary_items_category_id == 5 && $salary_item->salaryItemsName->is_threshold == 1){
                if($salary_item->is_percentage == 1){
                    return round(($gross_pay_before_tax * ($salary_item->amount / 100)), 2);
                } else{
                    return round($salary_item->amount, 2);
                }
            }
        }
        if($salary_item->salaryItemsName->salaryItemsCategory->id == 6){
            // $categoryOneAmount = $this->getCategoryIdOneAmount(1, $salary_item->company_id, $salary_item->employee_id);
            $gross_pay_before_tax = ($this->getAmountForEmployee(1, $salary_item->employee_id, $from_date, $to_date) - $this->getAmountForEmployee(2, $salary_item->employee_id, $from_date, $to_date)) + $this->getAmountForEmployee(3, $salary_item->employee_id, $from_date, $to_date);
            if($salary_item->is_percentage == 1){
                return round(($gross_pay_before_tax * ($salary_item->amount / 100)), 2);
            }
            return round($salary_item->amount, 2);
        }
        return $salary_item->amount;
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

    public function get_pay_frequency($employee_id)
    {
        $employee_associated_amount = EmployeeSalaryItem::query()
            ->where('employee_id', $employee_id)
            ->whereHas(
                'salaryItemsName', function (Builder $builder) {
                    $builder
                        ->where('name', 'Wages')
                        ->where('salary_items_category_id', 1);
                }
            )
            ->sum('amount');

        // CHECK IF HOURLY PAY
        if($employee_associated_amount <= 0){
            return Payslip::PAY_FREQUENCY_HOURLY;
        }
        return Payslip::PAY_FREQUENCY_MONTHLY;
    }

    public function get_basic_amount($employee_id, $from_date, $to_date)
    {
        $employee_associated_amount = EmployeeSalaryItem::query()
            ->where('employee_id', $employee_id)
            ->whereHas(
                'salaryItemsName', function (Builder $builder) {
                    $builder
                        ->where('name', 'Wages')
                        ->where('salary_items_category_id', 1);
                }
            )
            ->sum('amount');

        // CHECK IF HOURLY PAY
        if($employee_associated_amount <= 0){
            $hourly_amount = EmployeeSalaryItem::query()
                ->where('employee_id', $employee_id)
                ->whereHas(
                    'salaryItemsName', function (Builder $builder) {
                        $builder
                            ->where('name', 'Ordinary Time Rate')
                            ->where('salary_items_category_id', 1);
                    }
                )
                ->first('amount');
            if(request('working_hours')){
                $hours_worked = (int)request('working_hours');
            } else{
                $hours_worked = (int)$this->get_hours_worked($employee_id, $from_date, $to_date);
            }

            // $hours_worked = $this->get_hours_worked(request('employee_id'), request('from_date'), request('to_date'));
            if($hours_worked < 0){
                $employee_associated_amount = 0;
            } else{
                $employee_associated_amount = $hourly_amount->amount * $hours_worked;
            }
        }

        return $employee_associated_amount;
    }

    public function getLeaveData($employee_id, $leave, $from_date, $to_date)
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
                        'leave_details', function(Builder $builder)use($from_date, $to_date){
                            $builder->whereBetween(
                                'dates',
                                [
                                    $from_date,
                                    $to_date
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
                        'leave_details', function(Builder $builder)use($from_date, $to_date){
                            $builder->whereBetween(
                                'dates',
                                [
                                    $from_date,
                                    $to_date
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
        } elseif($leave == "absent")
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
                        'leave_details', function(Builder $builder)use($from_date, $to_date){
                            $builder->whereBetween(
                                'dates',
                                [
                                    $from_date,
                                    $to_date
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
        } elseif($leave == "unpaid_sick_leave"){
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
                        'leave_details', function(Builder $builder)use($from_date, $to_date){
                            $builder->whereBetween(
                                'dates',
                                [
                                    $from_date,
                                    $to_date
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
    }

    public function get_staff_deduction_sick_absent_amount($employee_id, $company_id, $from_date, $to_date)
    {
        $total = 0;
        if(request('absent')){
            $total += (int)request('absent') * $this->getSalaryItemAmount("Absent Rate", $employee_id)->amount;
        } else{
            $total += $this->getLeaveData($employee_id, "absent", $from_date, $to_date) * $this->getSalaryItemAmount("Absent Rate", $employee_id)->amount;
        }
        if(request('unpaid_sick_leave')){
            $total += (int)request('unpaid_sick_leave') * $this->getSalaryItemAmount("Unpaid Sick Leave Rate", $employee_id)->amount;
        } else{
            $total += $this->getLeaveData($employee_id, "unpaid_sick_leave", $from_date, $to_date) * $this->getSalaryItemAmount("Unpaid Sick Leave Rate", $employee_id, )->amount;
            Log::info('unpaid_sick_leave', [
                'a' => $total
            ]);
        }
        return $total; // this calculation is done from payload when creating payslips.

        $unpaid_absent_count = UserLeave::query()
                                    ->whereHas(
                                        'salary_item', function(Builder $builder)use($company_id){
                                            $builder->where('company_id', $company_id)
                                                    ->where(function($query){
                                                        $query->where('name', 'Absent')
                                                            ->orWhere('name', 'Unpaid Sick Leave');
                                                    });
                                        }
                                    )
                                    ->where('user_id', $employee_id)
                                    ->get();
            $count = 0;
            foreach($unpaid_absent_count as $value)
            {
                $count = $value?->leave_details?->count();
            }

            // get absent, unpaid leave value
            $absent_unpaid_value = EmployeeSalaryItem::query()
                ->whereHas(
                    'salaryItemsName', function (Builder $builder) {
                        $builder->where(function($query){
                                $query->where('name', 'Absent')
                                    ->orWhere('name', 'Unpaid Sick Leave');
                            })
                        ->whereHas(
                            'salaryItemsCategory', function (Builder $builder) {
                                $builder->where('id', 2);
                            }
                        );
                    }
                )
                ->where('company_id', $company_id)
                ->where('employee_id', $employee_id)
                ->get();
            return $count * ($absent_unpaid_value->count() > 0 ? $absent_unpaid_value[0]->amount : 0);
    }

    public function get_taxable_allowance_amount($employee_id, $company_id)
    {
        $employee_associated_amount = EmployeeSalaryItem::query()
            // ->where('employee_id', $employee_id)
            ->whereHas(
                'salaryItemsName', function (Builder $builder) {
                    $builder->where('salary_items_category_id', 3);
                }
            )
            ->where('company_id', $company_id)
            ->where(function ($query) use($employee_id){
                $query->where('employee_id', $employee_id)
                        ->orWhere(function ($query) {
                            $query->whereNull('employee_id')
                                ->where('is_general', 1);
                        });
            })
            ->sum('amount');
        return $employee_associated_amount;
    }

    public function get_non_taxable_allowance_amount($employee_id, $company_id)
    {
        $employee_associated_amount = EmployeeSalaryItem::query()
            // ->where('employee_id', $employee_id)
            ->whereHas(
                'salaryItemsName', function (Builder $builder) {
                    $builder->where('salary_items_category_id', 4);
                }
            )
            ->where('company_id', $company_id)
            ->where(function ($query) use($employee_id){
                $query->where('employee_id', $employee_id)
                        ->orWhere(function ($query) {
                            $query->whereNull('employee_id')
                                ->where('is_general', 1);
                        });
            })
            ->sum('amount');
        return $employee_associated_amount;
    }

    public function get_income_taxes_amount($employee_id, $company_id, $from_date, $to_date)
    {
        // $categoryOneAmount = $this->getCategoryIdOneAmount(1, $company_id, $employee_id);
        $gross_pay_before_tax = ($this->getAmountForEmployee(1, $employee_id, $from_date, $to_date) - $this->getAmountForEmployee(2, $employee_id, $from_date, $to_date)) + $this->getAmountForEmployee(3, $employee_id, $from_date, $to_date);
        $straight_without_percentage = EmployeeSalaryItem::query()
            ->whereHas(
                'salaryItemsName', function (Builder $builder) {
                    $builder->where('is_threshold', 1)
                            ->where('salary_items_category_id', 5);
                }
            )
            ->where('company_id', $company_id)
            ->where('is_percentage', 0)
            ->where(function ($query) use($employee_id){
                $query->where('employee_id', $employee_id)
                        ->orWhere(function ($query) {
                            $query->whereNull('employee_id')
                                ->where('is_general', 1);
                        });
            })
            ->sum('amount');
        $straight_percentage = EmployeeSalaryItem::query()
            ->whereHas(
                'salaryItemsName', function (Builder $builder) {
                    $builder->where('is_threshold', 1)
                            ->where('salary_items_category_id', 5);
                }
            )
            ->where('company_id', $company_id)
            ->where('is_percentage', 1)
            ->where(function ($query) use($employee_id){
                $query->where('employee_id', $employee_id)
                        ->orWhere(function ($query) {
                            $query->whereNull('employee_id')
                                ->where('is_general', 1);
                        });
            })
            ->get();

        $straight_percentage_value = 0;
        foreach($straight_percentage as $value)
        {
            $straight_percentage_value += ($gross_pay_before_tax * ($value->amount / 100));
        }

        $straight = $straight_without_percentage + $straight_percentage_value;

        $threshold = EmployeeSalaryItem::query()
            // ->where('employee_id', $employee_id)
            ->whereHas(
                'salaryItemsName', function (Builder $builder) {
                    $builder->where('is_threshold', 2)
                            ->where('salary_items_category_id', 5);
                }
            )
            ->where('company_id', $company_id)
            ->where('is_percentage', 1)
            ->where(function ($query) use($employee_id){
                $query->where('employee_id', $employee_id)
                        ->orWhere(function ($query) {
                            $query->whereNull('employee_id')
                                ->where('is_general', 1);
                        });
            })
            ->get();
        $threshold_value = 0;
        foreach($threshold as $value){
            $percentage_amount = $value->amount; // get the percentage value
            $threshold_details = $value->threshold_details; // get threshold details to check if the wages is between the details
            if($threshold_details)
            {
                $start_percentage_after = $threshold_details->start_percentage_after;
                $end_percentage_at = $threshold_details->end_percentage_at;

                if ($start_percentage_after <= $gross_pay_before_tax && (is_null($end_percentage_at) || $end_percentage_at >= $gross_pay_before_tax)) {
                    $threshold_value += ($gross_pay_before_tax * ($percentage_amount / 100));
                }
            }
        }
        return round($straight + $threshold_value, 2);
    }

    public function get_additional_taxes_tax_top_up_amount($employee_id, $company_id, $from_date, $to_date)
    {
        $user = User::where('id', $employee_id)->first();
        // $categoryOneAmount = $this->getCategoryIdOneAmount(1, $company->company_id, $employee_id);
        $gross_pay_before_tax = ($this->getAmountForEmployee(1, $employee_id, $from_date, $to_date) - $this->getAmountForEmployee(2, $employee_id, $from_date, $to_date)) + $this->getAmountForEmployee(3, $employee_id, $from_date, $to_date);

        $without_percentage_value =  EmployeeSalaryItem::query()
            ->whereHas(
                'salaryItemsName', function (Builder $builder) {
                    $builder->whereHas(
                                'salaryItemsCategory', function (Builder $builder) {
                                    $builder->where('id', 6);
                                }
                    );
                }
            )
            ->where('company_id', $user->company_id)
            ->where('is_percentage', 0)
            ->where(function ($query) use($employee_id){
                $query->where('employee_id', $employee_id)
                        ->orWhere(function ($query) {
                            $query->whereNull('employee_id')
                                ->where('is_general', 1);
                        });
            })
            ->get()->sum('amount');
        $with_percentage =  EmployeeSalaryItem::query()
            ->whereHas(
                'salaryItemsName', function (Builder $builder) {
                    $builder->whereHas(
                                'salaryItemsCategory', function (Builder $builder) {
                                    $builder->where('id', 6);
                                }
                    );
                }
            )
            ->where('company_id', $user->company_id)
            ->where('is_percentage', 1)
            ->where(function ($query) use($employee_id){
                $query->where('employee_id', $employee_id)
                        ->orWhere(function ($query) {
                            $query->whereNull('employee_id')
                                ->where('is_general', 1);
                        });
            })
            ->get();
        $with_percentage_value = 0;
        foreach($with_percentage as $value)
        {
            $with_percentage_value += ($gross_pay_before_tax * ($value->amount / 100));
        }

        return round($without_percentage_value + $with_percentage_value, 2);
    }

    public function government_deduction_amount($employee_id, $company_id, $from_date, $to_date)
    {
        // GET VALUE FROM CATEGORY 7
        $employee_contribution = DeductionDetails::query()
            ->whereHas(
                'employee_salary_item', function (Builder $builder) use ($employee_id, $company_id) {
                    $builder
                        ->where('company_id', $company_id)
                        ->where(function ($query) use($employee_id){
                            $query->where('employee_id', $employee_id)
                                  ->orWhere(function ($query) {
                                      $query->whereNull('employee_id')
                                            ->where('is_general', 1);
                                  });
                        })
                        ->whereHas(
                            'salaryItemsName', function (Builder $builder){
                                $builder->whereHas(
                                    'salaryItemsCategory', function (Builder $builder){
                                        $builder->where('id', 7);
                                    }
                                );
                            }
                        );
                }
            )
            ->get();
            // ->sum('employee_amount');

        $employee_contribution_value = 0;
        foreach($employee_contribution as $value)
        {
            $gross_pay_before_tax = ($this->getAmountForEmployee(1, $employee_id, $from_date, $to_date) - $this->getAmountForEmployee(2, $employee_id, $from_date, $to_date)) + $this->getAmountForEmployee(3, $employee_id, $from_date, $to_date);
            if($value->employee_salary_item->is_percentage == 1){
                $employee_contribution_value += round($gross_pay_before_tax * ($value->employee_amount / 100), 2);
            } else{
                $employee_contribution_value += round($value->employee_amount, 2);
            }
        }


        // GET VALUE FROM CATEGORY 8
        $other_company_deduction = DeductionDetails::query()
            ->whereHas(
                'employee_salary_item', function (Builder $builder) use ($employee_id, $company_id) {
                    $builder
                        ->where('company_id', $company_id)
                        ->where(function ($query) use($employee_id){
                            $query->where('employee_id', $employee_id)
                                  ->orWhere(function ($query) {
                                      $query->whereNull('employee_id')
                                            ->where('is_general', 1);
                                  });
                        })
                        ->whereHas(
                            'salaryItemsName', function (Builder $builder){
                                $builder->whereHas(
                                    'salaryItemsCategory', function (Builder $builder){
                                        $builder->where('id', 8);
                                    }
                                );
                            }
                        );
                }
            )
            ->get();
            // ->sum('employee_amount');

        $other_company_deduction_value = 0;
        foreach($other_company_deduction as $value)
        {
            $gross_pay_before_tax = ($this->getAmountForEmployee(1, $employee_id, $from_date, $to_date) - $this->getAmountForEmployee(2, $employee_id, $from_date, $to_date)) + $this->getAmountForEmployee(3, $employee_id, $from_date, $to_date);
            if($value->employee_salary_item->is_percentage == 1){
                $other_company_deduction_value += round($gross_pay_before_tax * ($value->employee_amount / 100), 2);
            } else{
                $other_company_deduction_value += round($value->employee_amount, 2);
            }
        }

        return $employee_contribution_value + $other_company_deduction_value;
    }

    public function other_complimentary_deduction_amount($employee_id, $company_id, $from_date, $to_date)
    {
        // GET VALUE FROM CATEGORY 7
        $company_contribution = DeductionDetails::query()
            ->whereHas(
                'employee_salary_item', function (Builder $builder) use ($employee_id, $company_id) {
                    $builder
                        ->where('company_id', $company_id)
                        ->where(function ($query) use($employee_id){
                            $query->where('employee_id', $employee_id)
                                  ->orWhere(function ($query) {
                                      $query->whereNull('employee_id')
                                            ->where('is_general', 1);
                                  });
                        })
                        ->whereHas(
                            'salaryItemsName', function (Builder $builder){
                                $builder->whereHas(
                                    'salaryItemsCategory', function (Builder $builder){
                                        $builder->where('id', 7);
                                    }
                                );
                            }
                        );
                }
            )
            ->get();
            // ->sum('government_or_company_amount');

        $company_contribution_value = 0;
        foreach($company_contribution as $value)
        {
            $gross_pay_before_tax = ($this->getAmountForEmployee(1, $employee_id, $from_date, $to_date) - $this->getAmountForEmployee(2, $employee_id, $from_date, $to_date)) + $this->getAmountForEmployee(3, $employee_id, $from_date, $to_date);
            if($value->employee_salary_item->is_percentage == 1){
                $company_contribution_value += round($gross_pay_before_tax * ($value->government_or_company_amount / 100), 2);
            } else{
                $company_contribution_value += round($value->government_or_company_amount, 2);
            }
        }

        // GET VALUE FROM CATEGORY 8
        $other_company_contribution = DeductionDetails::query()
            ->whereHas(
                'employee_salary_item', function (Builder $builder) use ($employee_id, $company_id) {
                    $builder
                        ->where('company_id', $company_id)
                        ->where(function ($query) use($employee_id){
                            $query->where('employee_id', $employee_id)
                                  ->orWhere(function ($query) {
                                      $query->whereNull('employee_id')
                                            ->where('is_general', 1);
                                  });
                        })
                        ->whereHas(
                            'salaryItemsName', function (Builder $builder){
                                $builder->whereHas(
                                    'salaryItemsCategory', function (Builder $builder){
                                        $builder->where('id', 8);
                                    }
                                );
                            }
                        );
                }
            )
            ->get();
            // ->sum('government_or_company_amount');

        $other_company_contribution_value = 0;
        foreach($other_company_contribution as $value)
        {
            $gross_pay_before_tax = ($this->getAmountForEmployee(1, $employee_id, $from_date, $to_date) - $this->getAmountForEmployee(2, $employee_id, $from_date, $to_date)) + $this->getAmountForEmployee(3, $employee_id, $from_date, $to_date);
            if($value->employee_salary_item->is_percentage == 1){
                $other_company_contribution_value += round($gross_pay_before_tax * ($value->government_or_company_amount / 100), 2);
            } else{
                $other_company_contribution_value += round($value->government_or_company_amount, 2);
            }
        }

        return $company_contribution_value + $other_company_contribution_value;
    }

    public function company_contribution_value($employee_id, $company_id, $from_date, $to_date)
    {
        $company_contribution = DeductionDetails::query()
            ->whereHas(
                'employee_salary_item', function (Builder $builder) use ($employee_id, $company_id) {
                    $builder
                        ->where('company_id', $company_id)
                        ->where(function ($query) use($employee_id){
                            $query->where('employee_id', $employee_id)
                                  ->orWhere(function ($query) {
                                      $query->whereNull('employee_id')
                                            ->where('is_general', 1);
                                  });
                        })
                        ->whereHas(
                            'salaryItemsName', function (Builder $builder){
                                $builder->whereHas(
                                    'salaryItemsCategory', function (Builder $builder){
                                        $builder->where('id', 7);
                                    }
                                );
                            }
                        );
                }
            )
            ->get();
            // ->sum('government_or_company_amount');

        $company_contribution_value = 0;
        foreach($company_contribution as $value)
        {
            $gross_pay_before_tax = ($this->getAmountForEmployee(1, $employee_id, $from_date, $to_date) - $this->getAmountForEmployee(2, $employee_id, $from_date, $to_date)) + $this->getAmountForEmployee(3, $employee_id, $from_date, $to_date);
            if($value->employee_salary_item->is_percentage == 1){
                $company_contribution_value += round($gross_pay_before_tax * ($value->government_or_company_amount / 100), 2);
            } else{
                $company_contribution_value += round($value->government_or_company_amount, 2);
            }
        }
        return $company_contribution_value;
    }

    public function employee_contribution_value($employee_id, $company_id, $from_date, $to_date)
    {
        $employee_deduction = DeductionDetails::query()
            ->whereHas(
                'employee_salary_item', function (Builder $builder) use ($employee_id, $company_id) {
                    $builder
                        ->where('company_id', $company_id)
                        ->where(function ($query) use($employee_id){
                            $query->where('employee_id', $employee_id)
                                  ->orWhere(function ($query) {
                                      $query->whereNull('employee_id')
                                            ->where('is_general', 1);
                                  });
                        })
                        ->whereHas(
                            'salaryItemsName', function (Builder $builder){
                                $builder->whereHas(
                                    'salaryItemsCategory', function (Builder $builder){
                                        $builder->where('id', 7);
                                    }
                                );
                            }
                        );
                }
            )
            ->get();
            // ->sum('employee_amount');

        $employee_deduction_value = 0;
        foreach($employee_deduction as $value)
        {
            $gross_pay_before_tax = ($this->getAmountForEmployee(1, $employee_id, $from_date, $to_date) - $this->getAmountForEmployee(2, $employee_id, $from_date, $to_date)) + $this->getAmountForEmployee(3, $employee_id, $from_date, $to_date);
            if($value->employee_salary_item->is_percentage == 1){
                $employee_deduction_value += round($gross_pay_before_tax * ($value->employee_amount / 100), 2);
            } else{
                $employee_deduction_value += round($value->employee_amount, 2);
            }
        }
        return $employee_deduction_value;
    }

    public function other_company_deduction($employee_id, $company_id, $from_date, $to_date)
    {
        $other_company_deduction = DeductionDetails::query()
            ->whereHas(
                'employee_salary_item', function (Builder $builder) use ($employee_id, $company_id) {
                    $builder
                        ->where('company_id', $company_id)
                        ->where(function ($query) use($employee_id){
                            $query->where('employee_id', $employee_id)
                                  ->orWhere(function ($query) {
                                      $query->whereNull('employee_id')
                                            ->where('is_general', 1);
                                  });
                        })
                        ->whereHas(
                            'salaryItemsName', function (Builder $builder){
                                $builder->whereHas(
                                    'salaryItemsCategory', function (Builder $builder){
                                        $builder->where('id', 8);
                                    }
                                );
                            }
                        );
                }
            )
            ->get();
            // ->sum('employee_amount');

        $other_company_deduction_value = 0;
        foreach($other_company_deduction as $value)
        {
            $gross_pay_before_tax = ($this->getAmountForEmployee(1, $employee_id, $from_date, $to_date) - $this->getAmountForEmployee(2, $employee_id, $from_date, $to_date)) + $this->getAmountForEmployee(3, $employee_id, $from_date, $to_date);
            if($value->employee_salary_item->is_percentage == 1){
                $other_company_deduction_value += round($gross_pay_before_tax * ($value->employee_amount / 100), 2);
            } else{
                $other_company_deduction_value += round($value->employee_amount, 2);
            }
        }
        return $other_company_deduction_value;
    }

    public function other_company_contribution($employee_id, $company_id, $from_date, $to_date)
    {
        $other_company_contribution = DeductionDetails::query()
            ->whereHas(
                'employee_salary_item', function (Builder $builder) use ($employee_id, $company_id) {
                    $builder
                        ->where('company_id', $company_id)
                        ->where(function ($query) use($employee_id){
                            $query->where('employee_id', $employee_id)
                                  ->orWhere(function ($query) {
                                      $query->whereNull('employee_id')
                                            ->where('is_general', 1);
                                  });
                        })
                        ->whereHas(
                            'salaryItemsName', function (Builder $builder){
                                $builder->whereHas(
                                    'salaryItemsCategory', function (Builder $builder){
                                        $builder->where('id', 8);
                                    }
                                );
                            }
                        );
                }
            )
            ->get();
            // ->sum('government_or_company_amount');

        $other_company_contribution_value = 0;
        foreach($other_company_contribution as $value)
        {
            $gross_pay_before_tax = ($this->getAmountForEmployee(1, $employee_id, $from_date, $to_date) - $this->getAmountForEmployee(2, $employee_id, $from_date, $to_date)) + $this->getAmountForEmployee(3, $employee_id, $from_date, $to_date);
            if($value->employee_salary_item->is_percentage == 1){
                $other_company_contribution_value += round($gross_pay_before_tax * ($value->government_or_company_amount / 100), 2);
            } else{
                $other_company_contribution_value += round($value->government_or_company_amount, 2);
            }
        }
        return $other_company_contribution_value;
    }

    // hours worked for employee
    public function get_hours_worked($employee_id, $from_date, $to_date)
    {
        if(request('working_hours')){
            $hours_worked = (int)request('working_hours');
        } else{
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
            $hours_worked = 0;
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
                    $hours_worked += $timeDifferenceMinutes;
                }
            }
        }
        return $hours_worked;
    }

    public function getSalaryItemAmount($name, $employee_id)
    {
        return EmployeeSalaryItem::query()
                    ->where('employee_id', $employee_id)
                    ->whereHas(
                        'salaryItemsName', function (Builder $builder) use($name) {
                            $builder
                                ->where('name', $name)
                                ->where('salary_items_category_id', 1);
                        }
                    )
                    ->first('amount');
    }

    public function getCategoryOneOtherValues($category_id, $employee_id)
    {
        $overtime = null;
        if(request('overtime')){
            $overtime = (int)request('overtime') * $this->getSalaryItemAmount("Overtime Rate", $employee_id)->amount;
        }
        $double_overtime = null;
        if(request('double_overtime')){
            $double_overtime = (int)request('double_overtime') * $this->getSalaryItemAmount("Double Overtime Rate", $employee_id)->amount;
        }
        $bonus = null;
        if(request('bonus')){
            $bonus = (int)request('bonus') * $this->getSalaryItemAmount("Bonus", $employee_id)->amount;
        }
        return [
            'overtime' => $overtime,
            'double_overtime' => $double_overtime,
            'bonus' => $bonus
        ];
    }

    // employee-salary-items-calculation
    public function getAmountForEmployee($category_id, $employee_id, $from_date, $to_date)
    {
        $user = User::where('id', $employee_id)->first();
        if ($category_id == 7) {
            $employee_deduction = DeductionDetails::query()
                ->whereHas(
                    'employee_salary_item', function (Builder $builder) use ($category_id, $employee_id, $user) {
                        $builder
                            ->where('company_id', $user->company_id)
                            ->where(function ($query) use($employee_id){
                                $query->where('employee_id', $employee_id)
                                      ->orWhere(function ($query) {
                                          $query->whereNull('employee_id')
                                                ->where('is_general', 1);
                                      });
                            })
                            ->whereHas(
                                'salaryItemsName', function (Builder $builder) use ($category_id) {
                                    $builder->whereHas(
                                        'salaryItemsCategory', function (Builder $builder) use ($category_id) {
                                            $builder->where('id', 7);
                                        }
                                    );
                                }
                            );
                    }
                )
                ->get();
                // ->sum('employee_amount');

            $employee_deduction_value = 0;
            foreach($employee_deduction as $value)
            {
                $gross_pay_before_tax = ($this->getAmountForEmployee(1, $employee_id, $from_date, $to_date) - $this->getAmountForEmployee(2, $employee_id, $from_date, $to_date)) + $this->getAmountForEmployee(3, $employee_id, $from_date, $to_date);
                if($value->employee_salary_item->is_percentage == 1){
                    $employee_deduction_value += round($gross_pay_before_tax * ($value->employee_amount / 100), 2);
                } else{
                    $employee_deduction_value += round($value->employee_amount, 2);
                }
            }

            // GET CATEGORY ID 8 DEDUCTION VALUE
            $other_complimentary_deduction = DeductionDetails::query()
                ->whereHas(
                    'employee_salary_item', function (Builder $builder) use ($category_id, $employee_id, $user) {
                        $builder
                            ->where('company_id', $user->company_id)
                            ->where(function ($query) use($employee_id){
                                $query->where('employee_id', $employee_id)
                                      ->orWhere(function ($query) {
                                          $query->whereNull('employee_id')
                                                ->where('is_general', 1);
                                      });
                            })
                            ->whereHas(
                                'salaryItemsName', function (Builder $builder) use ($category_id) {
                                    $builder->whereHas(
                                        'salaryItemsCategory', function (Builder $builder) use ($category_id) {
                                            $builder->where('id', 8);
                                        }
                                    );
                                }
                            );
                    }
                )
                ->get();
                // ->sum('employee_amount');

                $other_deduction_value = 0;
                foreach($other_complimentary_deduction as $value)
                {
                    $gross_pay_before_tax = ($this->getAmountForEmployee(1, $employee_id, $from_date, $to_date) - $this->getAmountForEmployee(2, $employee_id, $from_date, $to_date)) + $this->getAmountForEmployee(3, $employee_id, $from_date, $to_date);
                    if($value->employee_salary_item->is_percentage == 1){
                        $other_deduction_value += round($gross_pay_before_tax * ($value->employee_amount / 100), 2);
                    } else{
                        $other_deduction_value += round($value->employee_amount, 2);
                    }
                }
            return round($employee_deduction_value + $other_deduction_value, 2);
        } elseif ($category_id == 8) {
            return [
                'company_government_contribution' => 0.00,
                'company_other_complimentary_contribution' => 0.00,
                'company_contribution' => 0.00,
            ];

            return EmployeeSalaryItem::query()
                ->whereHas(
                    'salaryItemsName', function (Builder $builder) use ($category_id) {
                        $builder->whereHas(
                            'salaryItemsCategory', function (Builder $builder) use ($category_id) {
                                $builder->where('id', $category_id);
                            }
                        );
                    }
                )
                ->where('company_id', $user->company_id)
                ->where('employee_id', request('employee_id'))
                ->get()->sum('amount');
        } elseif ($category_id == 1) {
            $overtime = 0;
            if(request('overtime')){
                $overtime = (int)request('overtime') * $this->getSalaryItemAmount("Overtime Rate", $employee_id)->amount;
            }
            $double_overtime = 0;
            if(request('double_overtime')){
                $double_overtime = (int)request('double_overtime') * $this->getSalaryItemAmount("Double Overtime Rate", $employee_id)->amount;
            }
            $bonus = 0;
            if(request('bonus')){
                $bonus = (int)request('bonus') * $this->getSalaryItemAmount("Bonus", $employee_id)->amount;
            }
            $amount = EmployeeSalaryItem::query()
                ->whereHas(
                    'salaryItemsName', function (Builder $builder) {
                        $builder->where('name', 'Wages')
                            ->whereHas(
                                'salaryItemsCategory', function (Builder $builder) {
                                    $builder->where('id', 1);
                                }
                            );
                    }
                )
                ->where('company_id', $user->company_id)
                ->where('employee_id', $employee_id)
                ->get()->sum('amount');
            Log::info('log', [
                'user' => $user,
                'company' => $user->company_id,
                'user_id' => $employee_id
            ]);
            if($amount <= 0){
                $hourly_amount = EmployeeSalaryItem::query()
                    ->where('employee_id', $employee_id)
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
                return round(($employee_associated_amount + $overtime + $double_overtime + $bonus), 2);
            }
            return round(($amount + $overtime + $double_overtime + $bonus), 2);
        } elseif($category_id == 2){
            Log::info('a', [
                '1' => (int)request('absent'),
                '2' => $this->getSalaryItemAmount("Absent Rate", $employee_id)->amount,
                '3' => (int)$this->getLeaveData($employee_id, "absent", $from_date, $to_date)
            ]);
            $total = 0;
            if(request('absent')){
                $total += (int)request('absent') * $this->getSalaryItemAmount("Absent Rate", $employee_id)->amount;
            } else{
                Log::info('b', [
                    '1' => (int)request('absent'),
                    '2' => $this->getSalaryItemAmount("Absent Rate", $employee_id)->amount,
                    '3' => (int)$this->getLeaveData($employee_id, "absent", $from_date, $to_date)
                ]);
                $total += (int)$this->getLeaveData($employee_id, "absent", $from_date, $to_date) * $this->getSalaryItemAmount("Absent Rate", $employee_id)->amount;
            }
            if(request('unpaid_sick_leave')){
                $total += (int)request('unpaid_sick_leave') * $this->getSalaryItemAmount("Unpaid Sick Leave Rate", $employee_id)->amount;
            } else{
                Log::info('c', [
                    '1' => (int)request('absent'),
                    '2' => $this->getSalaryItemAmount("Absent Rate", $employee_id)->amount,
                    '3' => (int)$this->getLeaveData($employee_id, "absent", $from_date, $to_date)
                ]);
                $total += (int)$this->getLeaveData($employee_id, "unpaid_sick_leave", $from_date, $to_date) * $this->getSalaryItemAmount("Unpaid Sick Leave Rate", $employee_id)->amount;
            }
            Log::info('d', [
                '1' => $total
            ]);
            return $total; // this calculation is done from payload when creating payslips.

            $unpaid_absent_count = UserLeave::query()
                                    ->whereHas(
                                        'salary_item', function(Builder $builder)use($user){
                                            $builder->where('company_id', $user->company_id)
                                                    ->where(function($query){
                                                        $query->where('name', 'Absent')
                                                            ->orWhere('name', 'Unpaid Sick Leave');
                                                    });
                                        }
                                    )
                                    ->where('user_id', $employee_id)
                                    ->get();
            $count = 0;
            foreach($unpaid_absent_count as $value)
            {
                $count = $value?->leave_details?->count();
            }

            // get absent, unpaid leave value
            $absent_unpaid_value = EmployeeSalaryItem::query()
                ->whereHas(
                    'salaryItemsName', function (Builder $builder) use ($category_id) {
                        $builder->where(function($query){
                                $query->where('name', 'Absent')
                                    ->orWhere('name', 'Unpaid Sick Leave');
                            })
                        ->whereHas(
                            'salaryItemsCategory', function (Builder $builder) use ($category_id) {
                                $builder->where('id', 2);
                            }
                        );
                    }
                )
                ->where('company_id', $user->company_id)
                ->where('employee_id', $employee_id)
                ->get();
            return $count * ($absent_unpaid_value->count() > 0 ? $absent_unpaid_value[0]->amount : 0);
        } elseif($category_id == 5){
            $company = User::where('id', $employee_id)->first();
            // $categoryOneAmount = $this->getCategoryIdOneAmount(1, $company->company_id, $employee_id);
            $gross_pay_before_tax = ($this->getAmountForEmployee(1, $employee_id, $from_date, $to_date) - $this->getAmountForEmployee(2, $employee_id, $from_date, $to_date)) + $this->getAmountForEmployee(3, $employee_id, $from_date, $to_date);

            $straight_without_percentage = EmployeeSalaryItem::query()
                ->whereHas(
                    'salaryItemsName', function (Builder $builder) use ($category_id) {
                        $builder->where('is_threshold', 1)
                                ->whereHas(
                                    'salaryItemsCategory', function (Builder $builder) use ($category_id) {
                                        $builder->where('id', $category_id);
                                    }
                        );
                    }
                )
                ->where('company_id', $user->company_id)
                ->where('is_percentage', 0)
                ->where(function ($query) use($employee_id){
                    $query->where('employee_id', $employee_id)
                          ->orWhere(function ($query) {
                              $query->whereNull('employee_id')
                                    ->where('is_general', 1);
                          });
                })
                ->get()->sum('amount');
            $straight_percentage = EmployeeSalaryItem::query()
                ->whereHas(
                    'salaryItemsName', function (Builder $builder) use ($category_id) {
                        $builder->where('is_threshold', 1)
                                ->whereHas(
                                    'salaryItemsCategory', function (Builder $builder) use ($category_id) {
                                        $builder->where('id', $category_id);
                                    }
                        );
                    }
                )
                ->where('company_id', $user->company_id)
                ->where('is_percentage', 1)
                ->where(function ($query) use($employee_id){
                    $query->where('employee_id', $employee_id)
                        ->orWhere(function ($query) {
                            $query->whereNull('employee_id')
                                    ->where('is_general', 1);
                        });
                })
                ->get();
            $straight_percentage_value = 0;
            foreach($straight_percentage as $value)
            {
                $straight_percentage_value += ($gross_pay_before_tax * ($value->amount / 100));
            }

            $straight = $straight_without_percentage + $straight_percentage_value;

            $threshold =  EmployeeSalaryItem::query()
                ->whereHas(
                    'salaryItemsName', function (Builder $builder) use ($category_id) {
                        $builder->where('is_threshold', 2)
                                ->whereHas(
                                    'salaryItemsCategory', function (Builder $builder) use ($category_id) {
                                        $builder->where('id', $category_id);
                                    }
                        );
                    }
                )
                ->where('company_id', $user->company_id)
                ->where('is_percentage', 1)
                ->where(function ($query) use($employee_id){
                    $query->where('employee_id', $employee_id)
                          ->orWhere(function ($query) {
                              $query->whereNull('employee_id')
                                    ->where('is_general', 1);
                          });
                })
                ->get();

            $threshold_value = 0;
            foreach($threshold as $value){
                $percentage_amount = $value->amount; // get the percentage value
                $threshold_details = $value->threshold_details; // get threshold details to check if the wages is between the details
                if($threshold_details)
                {
                    // $start_percentage_after = $threshold_details->start_percentage_after;
                    // $end_percentage_at = $threshold_details->end_percentage_at;

                    // if ($start_percentage_after <= $gross_pay_before_tax && (is_null($end_percentage_at) || $end_percentage_at >= $gross_pay_before_tax)) {
                    //     $threshold_value += ($gross_pay_before_tax * ($percentage_amount / 100));
                    // }
                    $start = $threshold_details->start_percentage_after;
                    $end = $threshold_details->end_percentage_at;

                    if($gross_pay_before_tax > $start){
                        $taxableAmount = ($end === null || $gross_pay_before_tax < $end) ? $gross_pay_before_tax - ($start + 1) : ($start + 1);

                        $threshold_value += $taxableAmount * ($percentage_amount / 100);
                    }

                    if($gross_pay_before_tax < $end || $end === null){
                        break;
                    }
                }
            }
            return round($straight + $threshold_value, 2);
        } elseif($category_id == 6){
            $company = User::where('id', $employee_id)->first();
            // $categoryOneAmount = $this->getCategoryIdOneAmount(1, $company->company_id, $employee_id);
            $gross_pay_before_tax = ($this->getAmountForEmployee(1, $employee_id, $from_date, $to_date) - $this->getAmountForEmployee(2, $employee_id, $from_date, $to_date)) + $this->getAmountForEmployee(3, $employee_id, $from_date, $to_date);

            $without_percentage_value =  EmployeeSalaryItem::query()
                ->whereHas(
                    'salaryItemsName', function (Builder $builder) use ($category_id) {
                        $builder->whereHas(
                                    'salaryItemsCategory', function (Builder $builder) use ($category_id) {
                                        $builder->where('id', $category_id);
                                    }
                        );
                    }
                )
                ->where('company_id', $user->company_id)
                ->where('is_percentage', 0)
                ->where(function ($query) use($employee_id){
                    $query->where('employee_id', $employee_id)
                          ->orWhere(function ($query) {
                              $query->whereNull('employee_id')
                                    ->where('is_general', 1);
                          });
                })
                ->get()->sum('amount');
            $with_percentage =  EmployeeSalaryItem::query()
                ->whereHas(
                    'salaryItemsName', function (Builder $builder) use ($category_id) {
                        $builder->whereHas(
                                    'salaryItemsCategory', function (Builder $builder) use ($category_id) {
                                        $builder->where('id', $category_id);
                                    }
                        );
                    }
                )
                ->where('company_id', $user->company_id)
                ->where('is_percentage', 1)
                ->where(function ($query) use($employee_id){
                    $query->where('employee_id', $employee_id)
                          ->orWhere(function ($query) {
                              $query->whereNull('employee_id')
                                    ->where('is_general', 1);
                          });
                })
                ->get();
            $with_percentage_value = 0;
            foreach($with_percentage as $value)
            {
                $with_percentage_value += ($gross_pay_before_tax * ($value->amount / 100));
            }

            return round(($without_percentage_value + $with_percentage_value), 2);
        } else {
            $amount = EmployeeSalaryItem::query()
                ->whereHas(
                    'salaryItemsName', function (Builder $builder) use ($category_id) {
                        $builder->whereHas(
                            'salaryItemsCategory', function (Builder $builder) use ($category_id) {
                                $builder->where('id', $category_id);
                            }
                        );
                    }
                )
                ->where('company_id', $user->company_id)
                ->where(function ($query) use($employee_id){
                    $query->where('employee_id', $employee_id)
                          ->orWhere(function ($query) {
                              $query->whereNull('employee_id')
                                    ->where('is_general', 1);
                          });
                })
                ->get()->sum('amount');
            return round($amount, 2);
        }
    }

    public function getCategoryIdOneAmount($category_id, $company_id, $employee_id){
        $amount = EmployeeSalaryItem::query()
            ->whereHas(
                'salaryItemsName', function (Builder $builder) {
                    $builder->where('name', 'Wages')
                        ->whereHas(
                            'salaryItemsCategory', function (Builder $builder) {
                                $builder->where('id', 1);
                            }
                        );
                }
            )
            ->where('company_id', $company_id)
            ->where('employee_id', $employee_id)
            ->get()->sum('amount');
        if($amount <= 0){
            $hourly_amount = EmployeeSalaryItem::query()
                ->where('employee_id', $employee_id)
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
            return $employee_associated_amount;
        }
        return $amount;
    }
}
