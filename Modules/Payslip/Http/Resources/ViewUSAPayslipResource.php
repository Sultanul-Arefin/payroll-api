<?php

namespace Modules\Payslip\Http\Resources;

use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use JsonSerializable;
use Modules\LeaveManagement\Entities\UserLeave;
use Modules\LeaveManagement\Entities\UserLeaveDetail;
use Modules\Payslip\Entities\Payslip;
use Modules\Payslip\Entities\PayslipDetailsForDeduction;
use Modules\SalaryItemsName\Entities\LeaveSalaryItems;

class ViewUSAPayslipResource extends JsonResource
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
            'deduction_details' => $this->get_deduction_details(),
            'employee_id' =>$this->employee_id,
            'payment_date' => $this->payment_date,
            'fixed_pay_details' => $this->wages,
            'additional_pay' => $this->taxable_allowance,
            'wage_deduction' => $this->leave_deduction,
            'total_fixed_pay' => $this->taxable_allowance,
            'taxable_allowance' => $this->taxable_allowance,
            'non_taxable_allowance' => $this->non_taxable_allowance,
           // 'total_gross_pay' => $this->pay_due_before_deduction,
            //'taxable_gross_pay' => $this->gross_pay_before_tax,
            'total_gross_pay' => $this->wages - $this->leave_deduction + $this->taxable_allowance + $this->non_taxable_allowance,
            'tax_amount' => $this->tax_value + $this->post_tax_value,
            'taxable_gross_pay' => ($this->wages - $this->leave_deduction + $this->taxable_allowance),
            'pay_due_before_deduction' => $this->pay_due_before_deduction,
            //'staff_social_charges' => 0.00,
            'staff_social_charges' => $this->get_staff_social_charges(), // staff social charge goes here
            'other_deduction_summary' => $this->get_other_deduction(),
            'income_tax' => $this->getIncomeTaxAndCategoryDetails($this->payslip_details),
            'total_deductions' => $this->total_employee_deduction,
            'total_company_deduction' => $this->company_contribution,
            //'taxable_gross_pay' => $this->calculate_taxable_gross_pay(),
            //'overall_calculation' => $this->overall_calculation(),
            'total_net_pay' => $this->net_pay,
            'summary' => [
                'year_to_date' => $this->getYearToDateCalculations(),
            ],
            'annual_leave' => $this->get_annual_leave_calculation($this->employee)
        ];	
    }

    

    public function get_annual_leave_calculation($employee)
    {
        return [
            'annual_leave_quota' => $this->getAnnualLeaveQuota(),
            'annual_leave_taken' => $this->getAnnualLeaveTaken($this->employee->id),
            'remaining_annual_leave' => $this->getAnnualLeaveQuota() - $this->getAnnualLeaveTaken($this->employee->id),
            'sick_leave_quota' => $this->getSickLeaveQuota(),
            'sick_leave_taken' => $this->getSickLeaveTaken($this->employee->id),
            'remaining_sick_leave' => $this->getSickLeaveQuota() - $this->getSickLeaveTaken($this->employee->id),
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

    function getSickLeaveTaken($user_id): int
            {
                $sick_leave_data = LeaveSalaryItems::query()
                    ->whereHas(
                        'salary_items_name',
                        function (Builder $builder) {
                            $builder
                                ->where('name', 'Sick Leave')
                                ->where('company_id', auth()->user()->company_id);
                        }
                    )
                    ->first();

                $data = UserLeave::query()
                    ->where('user_id', $user_id)
                    ->where('leave_type', $sick_leave_data->salary_items_id)
                    ->where('status', UserLeave::APPROVED)
                    ->get();

                $count = 0;
                foreach ($data as $value) {
                    $details = UserLeaveDetail::query()
                        ->where('user_leaves_id', $value->id)
                        ->count();
                    $count += $details;
                }
                return $count;
            }

function getSickLeaveQuota(): int
        {
            $data = LeaveSalaryItems::query()
                ->whereHas(
                    'salary_items_name',
                    function (Builder $builder) {
                        $builder
                            ->where('name', 'Sick Leave')
                            ->where('company_id', auth()->user()->company_id);
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
    
        

        public function get_deduction_details()
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

        private function getIncomeTaxAndCategoryDetails($payslipDetails)
            {
                $filteredDetails = [];
                $totalAmount = 0;

                foreach ($payslipDetails as $detail) {
                    if (in_array($detail['category_id'], [5, 6])) { // Check if category_id is 5 or 6
                        $filteredDetails[] = [
                            'pay_details' => $detail['pay_details'],
                            'base_amount_or_hours' => $detail['base_amount_or_hours'],
                            'rate' => $detail['rate'],
                        ];
                        $totalAmount += $detail['rate']; // Accumulate the total rate
                    }
                }

                return [
                    'filtered_details' => $filteredDetails,
                    'total_amount' => $totalAmount,
                    'total_deductions_tax'=>$totalAmount + $this->total_employee_deduction,
                ];
            }


        // public function get_payslip_details($payslip_details){
        //     $payslip_value = 0;
        //     foreach($payslip_details as $payslip_detail){
        //         $payslip_detail->pay_details = $payslip_detail->salary_item->name;
        //         $payslip_detail->base_amount_or_hours = $payslip_detail->base_amount_or_hours;
        //         $payslip_detail->rate = $payslip_detail->amount;
        //         $payslip_detail->category_id = $payslip_detail?->salary_item?->salaryItemsCategory?->id;

        //         // payslip_calculation
        //         $payslip_value += $payslip_detail->amount;
        //         // unset these keys from the response
        //         unset($payslip_detail->salary_item, $payslip_detail->id, $payslip_detail->amount, $payslip_detail->created_at, $payslip_detail->updated_at, $payslip_detail->payslip_id, $payslip_detail->salary_item_id);
        //     }
        //     return [
        //         'payslip_details' => $payslip_details,
        //         'total_payslip_value_employee' => $this->pay_due_before_deduction,
        //     ];
        // }

        public function get_payslip_details($payslip_details)
        {
            $payslip_value = 0;
            $filteredDetails = [];
        
            foreach ($payslip_details as $payslip_detail) {
                $payslip_detail->pay_details = $payslip_detail->salary_item->name ?? 'N/A';
                $payslip_detail->base_amount_or_hours = $payslip_detail->base_amount_or_hours ?? 0;
                $payslip_detail->rate = $payslip_detail->amount ?? 0;
                $payslip_detail->category_id = $payslip_detail?->salary_item?->salaryItemsCategory?->id;
        
                // Skip category_id 5 and 6
                if (in_array($payslip_detail->category_id, [5, 6])) {
                    continue;
                }
        
                $payslip_value += $payslip_detail->rate;
        
                // Unset unnecessary keys
                unset(
                    $payslip_detail->salary_item,
                    $payslip_detail->id,
                    $payslip_detail->amount,
                    $payslip_detail->created_at,
                    $payslip_detail->updated_at,
                    $payslip_detail->payslip_id,
                    $payslip_detail->salary_item_id
                );
        
                $filteredDetails[] = $payslip_detail;
            }
        
            return [
                'payslip_details' => $filteredDetails,
                'total_payslip_value_employee' => $payslip_value,
            ];
        }
        

       

        public function getYearToDateCalculations()
                        {
                            $startOfYear = now()->startOfYear();
                            $yearToDatePayslips = Payslip::where('employee_id', $this->employee->id)
                                                ->whereBetween('payment_date', [$startOfYear, $this->payment_date])
                                                ->get();

                            // Calculate yearly gross pay
                            $yearToDateGrossPay = $yearToDatePayslips->sum(function($payslip) {
                                return $payslip->wages - $payslip->leave_deduction + $payslip->taxable_allowance + $payslip->non_taxable_allowance;
                            });

                            // Calculate yearly taxable gross pay
                            $yearToDateTaxableGross = $yearToDatePayslips->sum(function($payslip) {
                                return $payslip->wages - $payslip->leave_deduction + $payslip->taxable_allowance;
                            });

                            // Calculate yearly net pay 
                            $yearToDateNetPay = $yearToDatePayslips->sum(function($payslip) {
                                return $payslip->net_pay;
                            });

                            return [
                                'gross_pay' => $yearToDateGrossPay,
                                'taxable_gross' => $yearToDateTaxableGross,
                                'net_pay' => $yearToDateNetPay,
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
                    'total_gross_pay' => $this->pay_due_before_deduction,
                    //'taxable_gross_pay' => $this->gross_pay_before_tax - $this->non_taxable_allowance,
                    'taxable_gross_pay' => $this->calculate_taxable_gross_pay(),
                    'ytd_tax_paid' => 0,
                    'total_staff_contribution' => $this->total_employee_deduction,
                    'total_company_contribution' => $this->company_contribution,
                    'total_staff_cost' =>  $this->gross_pay_before_tax,
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
                    'total_gross_pay' => $this->pay_due_before_deduction,
                   // 'taxable_gross_pay' => $this->gross_pay_before_tax - $this->non_taxable_allowance,
                   'taxable_gross_pay' => $this->calculate_taxable_gross_pay(),
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