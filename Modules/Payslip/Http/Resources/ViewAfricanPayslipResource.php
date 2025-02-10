<?php

namespace Modules\Payslip\Http\Resources;

use Carbon\Carbon;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use JsonSerializable;
use Modules\Attendance\Entities\Attendance;
use Modules\EmployeeSalaryItems\Entities\EmployeeSalaryItem;
use Modules\LeaveManagement\Entities\UserLeave;
use Modules\LeaveManagement\Entities\UserLeaveDetail;
use Modules\Payslip\Entities\PayslipDetailsForDeduction;
use Modules\SalaryItemsName\Entities\LeaveSalaryItems;

class ViewAfricanPayslipResource extends JsonResource
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
            'items_details' => $this->get_payslip_details($this->payslip_details),
            'payment_date' => $this->payment_date, //date('Y-m-d H:i:s')
            'fixed_pay_details' => $this->wages,
            'additional_pay' => 0, // additional pay goes here
            'wage_deduction' => $this->leave_deduction,
            'total_fixed_pay' => $this->wages - $this->leave_deduction,
            'taxable_allowance' => $this->taxable_allowance,
            'non_taxable_allowance' => $this->non_taxable_allowance,
            'total_gross_pay' => $this->gross_pay_before_tax,
            'taxable_gross_pay' => $this->gross_pay_before_tax, // this is added due to question from Nabila. Have to check the calculation again
            // 'taxable_gross_pay' => $this->gross_pay_before_tax - $this->non_taxable_allowance,
            'tax_amount' => $this->tax_value + $this->post_tax_value,
            'gross_pay_after_tax' => $this->gross_pay_after_tax,
            'pay_due_before_deduction' => $this->pay_due_before_deduction,
            'staff_social_charges' => $this->get_staff_social_charges(), // staff social charge goes here
            'total_net_pay' => $this->net_pay,
            //'overall_calculation' => $this->overall_calculation(),
            'social_decution' => $this->get_social_deduction(),
            'other_decution' => $this->get_other_deduction(),
            'annual_leave' => $this->get_annual_leave_calculation($this->employee),
            'day_by_day_details' => $this->getDayByDayDetails($this->start_date, $this->end_date, $this->employee->id),
            // 'day_by_day_details' => $this->getDayByDayDetails($this->start_date, $this->end_date, $this->employee->id),
            
        ];
    }

    public function getDayByDayPayslipDetails($from_date, $to_date, $employee_id)
{
    $daily_details = [];

    // Ensure $from_date and $to_date are Carbon instances
    $from_date = $from_date ? Carbon::parse($from_date) : Carbon::parse($this->first_date);
    $to_date = $to_date ? Carbon::parse($to_date) : Carbon::parse($this->last_date);

    // Fetch attendance data
    $attendances = Attendance::with('attendance_details')
        ->where('user_id', $employee_id)
        ->whereBetween('dates', [$from_date->format('Y-m-d'), $to_date->format('Y-m-d')])
        ->get()
        ->keyBy('dates');

    // Fetch payslip details
    $payslipDetails = PayslipDetail::with('salary_item.salaryItemsCategory')
        ->where('employee_id', $employee_id)
        ->get();

    // Map payslip details by day for easy access
    $payslipDetailsMap = $payslipDetails->groupBy('dates');

    // Generate daily data
    $current_date = $from_date->copy();
    while ($current_date <= $to_date) {
        $dayData = [
            'day' => $current_date->format('l'),
            'date' => $current_date->format('Y-m-d'),
            'base_amount_or_hours' => 0,
            'pay_details' => [],
        ];

        // Check attendance for this date
        $attendanceForDate = $attendances->get($current_date->format('Y-m-d'));
        if ($attendanceForDate) {
            $dayData['base_amount_or_hours'] = $attendanceForDate->attendance_details->sum(function ($detail) {
                $inTime = Carbon::parse($detail->in_time);
                $outTime = Carbon::parse($detail->out_time);
                return $outTime->diffInHours($inTime);
            });
        }

        // Fetch payslip details for this date
        $payslipForDate = $payslipDetailsMap->get($current_date->format('Y-m-d'));
        if ($payslipForDate) {
            foreach ($payslipForDate as $payslip) {
                $dayData['pay_details'][] = [
                    'pay_details' => $payslip->salary_item->name,
                    'rate' => $payslip->amount,
                    'category_id' => $payslip->salary_item->salaryItemsCategory->id,
                ];
            }
        }

        $daily_details[] = $dayData;
        $current_date->addDay();
    }

    return $daily_details;
}

public function getDayByDayDetails($from_date, $to_date, $employee_id)
{
    $daily_details = [];

    // Ensure $from_date and $to_date are Carbon instances
    $from_date = $from_date ? Carbon::parse($from_date) : Carbon::parse($this->first_date);
    $to_date = $to_date ? Carbon::parse($to_date) : Carbon::parse($this->last_date);

    // Fetch Attendance data
    $attendances = Attendance::with('attendance_details')
        ->where('user_id', $employee_id)
        ->whereBetween('dates', [$from_date->format('Y-m-d'), $to_date->format('Y-m-d')])
        ->get()
        ->keyBy('dates');

    // Fetch Leave data
    $userLeaves = UserLeave::with('leave_details')
        ->where('user_id', $employee_id)
        ->whereHas('leave_details', function ($query) use ($from_date, $to_date) {
            $query->whereBetween('dates', [$from_date->format('Y-m-d'), $to_date->format('Y-m-d')]);
        })
        ->get();

    // Fetch wage data
    $wageRates = EmployeeSalaryItem::with('salaryItemsName')
        ->where('employee_id', $employee_id)
        ->whereHas('salaryItemsName', function ($query) {
            $query->where('salary_items_category_id', 1); // Assuming category 1 is wages
        })
        ->get()
        ->mapWithKeys(function ($item) {
            return [$item->salaryItemsName->name => $item->amount];
        });

    // Generate daily data
    $current_date = $from_date->copy();
    while ($current_date <= $to_date) {
        $dayData = [
            'day' => $current_date->format('l'),
            'date' => $current_date->format('Y-m-d'),
            'regular_hours' => 0,
            'wages' => [], // Placeholder for wage details
            'pay_details' => [], // Add pay_details here
            'annual_leave' => 0,
            'sick_leave' => 0,
        ];

        // Check attendance for this date
        $attendanceForDate = $attendances->get($current_date->format('Y-m-d'));
        if ($attendanceForDate) {
            $regular_hours = $attendanceForDate->attendance_details->sum(function ($detail) {
                $inTime = Carbon::parse($detail->in_time);
                $outTime = Carbon::parse($detail->out_time);
                return $outTime->diffInHours($inTime);
            });
            $dayData['regular_hours'] = $regular_hours;

            // Calculate wages for this day and populate pay_details
            foreach ($wageRates as $wageName => $rate) {
                $hourly_rate = $rate / 8; // Assuming 8 hours per workday
                $daily_rate = $hourly_rate * $regular_hours;
                $dayData['wages'][] = [
                    'name' => $wageName,
                    'daily_rate' => $daily_rate,
                ];

                $dayData['pay_details'][] = [
                    'name' => $wageName,
                    'amount' => $daily_rate,
                ];
            }
        }

        // Check leave for this date
        $leaveForDate = $userLeaves->first(function ($leave) use ($current_date) {
            return $leave->leave_details->contains('dates', $current_date->format('Y-m-d'));
        });

        if ($leaveForDate) {
            $leaveType = $leaveForDate->leave_type; // Assuming leave_type maps to SalaryItemsName
            if ($leaveType == 1) { // Assuming 1 is Annual Leave
                $dayData['annual_leave'] = 1;
            } elseif ($leaveType == 2) { // Assuming 2 is Sick Leave
                $dayData['sick_leave'] = 1;
            }

            $dayData['pay_details'][] = [
                'name' => $leaveType == 1 ? 'Annual Leave' : 'Sick Leave',
                'amount' => 0, // Deduction or additional amount based on leave type can be added here
            ];
        }

        $daily_details[] = $dayData;
        $current_date->addDay();
    }

    return $daily_details;
}

    public function getDayByDayDetailsd($from_date, $to_date, $employee_id)
    {
        $daily_details = [];

        // Ensure $from_date and $to_date are Carbon instances
        $from_date = $from_date ? Carbon::parse($from_date) : Carbon::parse($this->first_date);
        $to_date = $to_date ? Carbon::parse($to_date) : Carbon::parse($this->last_date);

        // Fetch Attendance data
        $attendances = Attendance::with('attendance_details')
            ->where('user_id', $employee_id)
            ->whereBetween('dates', [$from_date->format('Y-m-d'), $to_date->format('Y-m-d')])
            ->get()
            ->keyBy('dates');

        // Fetch Leave data
        $userLeaves = UserLeave::with('leave_details')
            ->where('user_id', $employee_id)
            ->whereHas('leave_details', function ($query) use ($from_date, $to_date) {
                $query->whereBetween('dates', [$from_date->format('Y-m-d'), $to_date->format('Y-m-d')]);
            })
            ->get();

        // Fetch wage data
        $wageRates = EmployeeSalaryItem::with('salaryItemsName')
            ->where('employee_id', $employee_id)
            ->whereHas('salaryItemsName', function ($query) {
                $query->where('salary_items_category_id', 1); // Assuming category 1 is wages
            })
            ->get()
            ->mapWithKeys(function ($item) {
                return [$item->salaryItemsName->name => $item->amount];
            });

        // Generate daily data
        $current_date = $from_date->copy();
        while ($current_date <= $to_date) {
            $dayData = [
                'day' => $current_date->format('l'),
                'date' => $current_date->format('Y-m-d'),
                'regular_hours' => 0,
                'wages' => [], // Placeholder for wage details
                'annual_leave' => 0,
                'sick_leave' => 0,
            ];

            // Check attendance for this date
            $attendanceForDate = $attendances->get($current_date->format('Y-m-d'));
            if ($attendanceForDate) {
                $regular_hours = $attendanceForDate->attendance_details->sum(function ($detail) {
                    $inTime = Carbon::parse($detail->in_time);
                    $outTime = Carbon::parse($detail->out_time);
                    return $outTime->diffInHours($inTime);
                });
                $dayData['regular_hours'] = $regular_hours;

                // Calculate wages for this day
                foreach ($wageRates as $wageName => $rate) {
                    $hourly_rate = $rate / 8; // Assuming 8 hours per workday
                    $dayData['wages'][] = [
                        'name' => $wageName,
                        'daily_rate' => $hourly_rate * $regular_hours,
                    ];
                }
            }

            // Check leave for this date
            $leaveForDate = $userLeaves->first(function ($leave) use ($current_date) {
                return $leave->leave_details->contains('dates', $current_date->format('Y-m-d'));
            });

            if ($leaveForDate) {
                $leaveType = $leaveForDate->leave_type; // Assuming leave_type maps to SalaryItemsName
                if ($leaveType == 1) { // Assuming 1 is Annual Leave
                    $dayData['annual_leave'] = 1;
                } elseif ($leaveType == 2) { // Assuming 2 is Sick Leave
                    $dayData['sick_leave'] = 1;
                }
            }

            $daily_details[] = $dayData;
            $current_date->addDay();
        }

        return $daily_details;
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
        foreach($payslip_details as $payslip_detail){
            $payslip_detail->pay_details = $payslip_detail->salary_item->name;
            $payslip_detail->base_amount_or_hours = $payslip_detail->base_amount_or_hours;
            $payslip_detail->rate = $payslip_detail->amount;
            $payslip_detail->category_id = $payslip_detail?->salary_item?->salaryItemsCategory?->id;

            // unset these keys from the response
            unset($payslip_detail->salary_item, $payslip_detail->id, $payslip_detail->amount, $payslip_detail->created_at, $payslip_detail->updated_at, $payslip_detail->payslip_id, $payslip_detail->salary_item_id);
        }
        return $payslip_details;
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
