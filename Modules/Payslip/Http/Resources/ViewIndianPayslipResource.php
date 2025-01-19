<?php

namespace Modules\Payslip\Http\Resources;

use Carbon\Carbon;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Database\Eloquent\Builder;
use JsonSerializable;
use Modules\Attendance\Entities\Attendance;
use Modules\LeaveManagement\Entities\UserLeave;
use Modules\LeaveManagement\Entities\UserLeaveDetail;
use Modules\Payslip\Entities\Payslip;
use Modules\Payslip\Entities\PayslipDetailsForDeduction;
use Modules\SalaryItemsName\Entities\LeaveSalaryItems;

class ViewIndianPayslipResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'items_details' => $this->get_payslip_details($this->payslip_details),
            'total_earnings' => $this->pay_due_before_deduction,
            'payment_date' => $this->payment_date, //date('Y-m-d H:i:s')
            'fixed_pay_details' => $this->wages,
            'additional_pay' => 0, // additional pay goes here
            'wage_deduction' => $this->leave_deduction,
            'total_fixed_pay' => $this->wages - $this->leave_deduction,
            'taxable_allowance' => $this->taxable_allowance,
            'non_taxable_allowance' => $this->non_taxable_allowance,
            'total_gross_pay' => $this->gross_pay_before_tax,
            'taxable_gross_pay' => $this->gross_pay_before_tax - $this->non_taxable_allowance,
            'tax_amount' => $this->tax_value + $this->post_tax_value,
            'gross_pay_after_tax' => $this->gross_pay_after_tax,
            'pay_due_before_deduction' => $this->pay_due_before_deduction,
            'staff_social_charges' => $this->get_staff_social_charges(), // staff social charge goes here
            'total_net_pay' => $this->net_pay, 
            'social_decution' => $this->get_social_deduction(),
            'other_decution' => $this->get_other_deduction(),
            'total_deductions' => $this->total_employee_deduction,
            'total_company_deduction' => $this->company_contribution,
            'annual_leave' => $this->get_annual_leave_calculation($this->employee),
            'attendance' => $this->getTotalAttendanceDays(
                $this->employee->id,
                $this->start_date,
                $this->end_date
            ),
            'net_salary' => $this->net_pay,
           // 'overall_calculation' => $this->overall_calculation(),
        ];
    }

    public function get_annual_leave_calculation($employee)
    {
        return [
            'annual_leave_quota' => $this->getAnnualLeaveQuota(),
            'annual_leave_taken' => $this->getAnnualLeaveTaken($this->employee->id),
            'remaining_annual_leave' => $this->getAnnualLeaveQuota() - $this->getAnnualLeaveTaken($this->employee->id)
        ];
    }

    function getAnnualLeaveTaken($user_id): int {
        $annual_leave_data = LeaveSalaryItems::query()
            ->whereHas(
                'salary_items_name', function(Builder $builder){
                    $builder
                    ->where(
                        'name',
                        'Annual Leave',
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

    function getAnnualLeaveQuota(): int {
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

            public function getTotalAttendanceDays($employee_id, $from_date, $to_date)
        { 
                if (!$from_date) {
                    $from_date = $this->first_date;
                }
                if (!$to_date) {
                    $to_date = $this->last_date;
                }

           
            $present_days = Attendance::query()
                ->where('user_id', $employee_id)
                ->whereBetween('dates', [$from_date, $to_date])
                ->where('status', Attendance::PRESENT) // Only count "Present" days
                ->count();

           
            $getLeaveDays = function ($employee_id, $leave_type_name, $from_date, $to_date) {
                $leave_data = LeaveSalaryItems::query()
                    ->whereHas('salary_items_name', function (Builder $builder) use ($leave_type_name) {
                        $builder
                            ->where('name', $leave_type_name)
                            ->where('company_id', auth()->user()->company_id);
                    })
                    ->first();

                if (!$leave_data) {
                    return 0; 
                }

                return UserLeave::query()
                    ->where('user_id', $employee_id)
                    ->where('status', UserLeave::APPROVED) // Only consider approved leaves
                    ->where('leave_type', $leave_data->salary_items_id)
                    ->whereHas('leave_details', function ($query) use ($from_date, $to_date) {
                        $query->whereBetween('dates', [$from_date, $to_date]);
                    })
                    ->withCount(['leave_details as leave_days_count' => function ($query) use ($from_date, $to_date) {
                        $query->whereBetween('dates', [$from_date, $to_date]);
                    }])
                    ->get()
                    ->sum('leave_days_count'); // Sum all the leave days for the user
            };

            // Calculate Annual Leave Days
            $annual_leave_days = $getLeaveDays($employee_id, 'Annual Leave', $from_date, $to_date);

            // Calculate Sick Leave Days
            $sick_leave_days = $getLeaveDays($employee_id, 'Sick Leave', $from_date, $to_date);
            $unpaid_leave_days = $getLeaveDays($employee_id, 'Unpaid Sick Leave', $from_date, $to_date);
            $Absent_leave_days = $getLeaveDays($employee_id, 'Absent', $from_date, $to_date);

            // Total approved leave days (Annual + Sick)
            $approved_leave_days = $annual_leave_days + $sick_leave_days;

            // Total attendance days = Present days + Approved leave days
            return [
                'total_working_days' => $present_days,
                //'annual_leave_days' => $annual_leave_days,
                //'sick_leave_days' => $sick_leave_days,
                'lop_days' => $Absent_leave_days,
                'leaves_taken' => $approved_leave_days,
                'paid_days' => $present_days + $approved_leave_days,
            ];
        }

        



        public function get_other_deduction()
        {
            $deduction_details = PayslipDetailsForDeduction::query()
                                ->whereHas(
                                    'salary_item_name', function(Builder $builder){
                                        $builder->where('salary_items_category_id', 8);
                                    }
                                )
                                ->where('payslip_id', $this->id)
                                ->get();
            $response = [];
            foreach($deduction_details as $value)
            {
                array_push($response, [
                    'title' => $value?->salary_item_name?->name,
                    'base' => $this->wages,
                    'employee_rate' => $value->employee_amount,
                    'employee_amount' => $value->employee_amount,
                    'company_rate' => $value->government_or_company_amount,
                    'company_amount' => $value->government_or_company_amount
                ]);
            }
            return $response;
        }
    
        public function get_social_deduction()
        {
            $deduction_details = PayslipDetailsForDeduction::query()
                                ->whereHas(
                                    'salary_item_name', function(Builder $builder){
                                        $builder->where('salary_items_category_id', 7);
                                    }
                                )
                                ->where('payslip_id', $this->id)
                                ->get();
            $response = [];
            foreach($deduction_details as $value)
            {
                array_push($response, [
                    'title' => $value?->salary_item_name?->name,
                    'base' => $this->wages,
                    'employee_rate' => $value->employee_amount,
                    'employee_amount' => $value->employee_amount,
                    'company_rate' => $value->government_or_company_amount,
                    'company_amount' => $value->government_or_company_amount
                ]);
            }
            return $response;
        }
    
        

    public function get_staff_social_charges()
    {
        return PayslipDetailsForDeduction::query()
                ->where('payslip_id', $this->id)
                ->sum('employee_amount');
    }

    public function get_payslip_details($payslip_details){
        $payslip_value = 0;
        foreach($payslip_details as $payslip_detail){
            $payslip_detail->pay_details = $payslip_detail->salary_item->name;
            $payslip_detail->base_amount_or_hours = $payslip_detail->base_amount_or_hours;
            $payslip_detail->rate = $payslip_detail->amount;
            $payslip_detail->category_id = $payslip_detail?->salary_item?->salaryItemsCategory?->id;

            $payslip_value += $payslip_detail->amount;

            // unset these keys from the response
            unset($payslip_detail->salary_item, $payslip_detail->id, $payslip_detail->amount, $payslip_detail->created_at, $payslip_detail->updated_at, $payslip_detail->payslip_id, $payslip_detail->salary_item_id);
        }
       // return $payslip_details;
       return [
        'payslip_details' => $payslip_details,
        //'total_earnings' => $payslip_value
    ];
    }

    public function overall_calculation()
    {
        return [
            'monthly' => [
                [
                    'hours' => $this->hours_worked,
                    'overtime_hours' => 0, // calculate full working hours & get overtime
                    'total_fixed_pay' => $this->wages - $this->leave_deduction,
                    'taxable_allowances' => $this->taxable_allowance,
                    'non_taxable_allowances' => $this->non_taxable_allowance,
                    'total_gross_pay' => $this->gross_pay_before_tax,
                    'taxable_gross_pay' => $this->gross_pay_before_tax - $this->non_taxable_allowance,
                    'ytd_tax_paid' => 0,
                    'total_staff_contribution' => $this->total_employee_deduction,
                    'total_company_contribution' => $this->company_contribution,
                    'total_staff_cost' => $this->net_pay,
                    'total_net_pay' => $this->net_pay,
                ],
            ],
            'yearly' => [
                [
                    'hours' => $this->hours_worked,
                    'overtime_hours' => 0,
                    'total_fixed_pay' => $this->wages - $this->leave_deduction,
                    'taxable_allowances' => $this->taxable_allowance,
                    'non_taxable_allowances' => $this->non_taxable_allowance,
                    'total_gross_pay' => $this->gross_pay_before_tax,
                    'taxable_gross_pay' => $this->gross_pay_before_tax - $this->non_taxable_allowance,
                    'ytd_tax_paid' => 0,
                    'total_staff_contribution' => $this->total_employee_deduction,
                    'total_company_contribution' => $this->company_contribution,
                    'total_staff_cost' => $this->net_pay,
                    'total_net_pay' => $this->net_pay,
                ],
            ],
            'total_net_pay' => $this->net_pay,
        ];
    }
}
