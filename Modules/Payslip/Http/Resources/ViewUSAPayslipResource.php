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
            'wage_deduction' => $this->taxable_allowance,
            'total_fixed_pay' => $this->taxable_allowance,
            'taxable_allowance' => $this->taxable_allowance,
            'non_taxable_allowance' => $this->non_taxable_allowance,
            'total_gross_pay' => $this->pay_due_before_deduction,
            //'taxable_gross_pay' => $this->gross_pay_before_tax,
            'tax_amount' => $this->tax_value + $this->post_tax_value,
            'pay_due_before_deduction' => $this->pay_due_before_deduction,
            //'staff_social_charges' => 0.00,
            'staff_social_charges' => $this->get_staff_social_charges(), // staff social charge goes here
            'other_deduction_summary' => $this->get_other_deduction(),
            'taxable_gross_pay' => $this->calculate_taxable_gross_pay(),
            'overall_calculation' => $this->overall_calculation(),
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

    private function calculate_taxable_gross_pay()
    {
        $gross_pay = $this->pay_due_before_deduction;
        
        // Get other deductions (should return an array of deductions)
        $other_deduction = $this->get_other_deduction();
        
        // Sum up all employee amounts in the other_deduction array
        if (is_array($other_deduction)) {
            $other_deduction_total = array_sum(array_column($other_deduction, 'employee_amount'));
        } else {
            $other_deduction_total = 0;
        }

        // Ensure taxable gross pay doesn't go negative
        $taxable_gross_pay = max(0, $gross_pay - $other_deduction_total);
        
        return $taxable_gross_pay;
    }
    

    public function get_other_deduction()
    {
        $deduction_details = PayslipDetailsForDeduction::query()
                                ->whereHas(
                                    'salary_item_name', function(Builder $builder){
                                        $builder->where('salary_items_category_id', 8);  // Category 8 for deductions
                                    }
                                )
                                ->where('payslip_id', $this->id)
                                ->get();
            
        $response = [];
        foreach($deduction_details as $value)
        {
            $yearlyAmount = $this->getYearlyDeductionForItem($value->salary_item_name->id);
                
            // Return the necessary data as an array
            array_push($response, [
                'title' => $value?->salary_item_name?->name,
                'base' => $this->wages,
                'employee_rate' => $value->employee_amount,
                'employee_amount' => $value->employee_amount,
                'yearly_employee_amount' => $yearlyAmount,
            ]);
        }
    
    return $response;  // Returns an array of deductions
}
    
        /**
         * Calculate the yearly total of employee_amount for a specific deduction item (category 8).
         *
         * @param int $salaryItemId
         * @return float
         */
        public function getYearlyDeductionForItem($salaryItemId)
        {
            // Define the start of the year
            $startOfYear = now()->startOfYear();
        
            // Get the total employee_amount for the year for this employee for the specific salary item
            return PayslipDetailsForDeduction::query()
                        ->whereHas('salary_item_name', function (Builder $builder) use ($salaryItemId) {
                            $builder->where('id', $salaryItemId)
                                    ->where('salary_items_category_id', 8);
                        })
                        ->whereHas('payslip', function (Builder $query) use ($startOfYear) {
                            $query->where('employee_id', $this->employee_id)
                                ->whereBetween('payment_date', [$startOfYear, now()]);
                        })
                        ->sum('employee_amount');
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

                        
            $deduction_value = 0;
            $deduction_values=0;
        
            foreach($deduction_details as $dv)
            {
                $dv->pay_details = $dv->salary_item_name?->name;
                $dv->employee_amount = $dv->employee_amount;
                $dv->government_or_company_amount = $dv->government_or_company_amount;

            //deduction_calculation
            $deEmployee=  $deduction_value += $dv->employee_amount;
            $deEmployer=  $deduction_values += $dv->government_or_company_amount;
                
                unset($dv->salary_item, $dv->id, $dv->amount, $dv->created_at, $dv->updated_at, $dv->payslip_id, $dv->salary_item_id,$dv->pay_details);
                //return $deduction_details;
            }
            return [
                'deduction_details' => $deduction_details,
                'total_deduction_value_employee' => $deEmployee ?? 0,
                'total_deduction_valueEmployer' => $deEmployer ?? 0,
                
            ];
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

                // payslip_calculation
                $payslip_value += $payslip_detail->amount;
                // unset these keys from the response
                unset($payslip_detail->salary_item, $payslip_detail->id, $payslip_detail->amount, $payslip_detail->created_at, $payslip_detail->updated_at, $payslip_detail->payslip_id, $payslip_detail->salary_item_id);
            }
            return [
                'payslip_details' => $payslip_details,
                'total_payslip_value_employee' => $payslip_value
            ];
        }

        public function getYearToDateCalculations()
        {
            $startOfYear = now()->startOfYear();
            $yearToDatePayslips = Payslip::where('employee_id', $this->employee->id)
                                ->whereBetween('payment_date', [$startOfYear, $this->payment_date])
                                ->get();

            $yearToDateGrossPay = $yearToDatePayslips->sum('pay_due_before_deduction');

            $yearToDateTaxableGross =$yearToDateGrossPay - ( $yearToDatePayslips->sum('other_company_deduction'));
            $yearToDateTotalDeductions = $yearToDatePayslips->sum('employee_contribution_value');
            $yearToDateNetPay =  $yearToDateTaxableGross - $yearToDateTotalDeductions;

            return [
                'gross_pay' => $yearToDateGrossPay,
                'taxable_gross' => $yearToDateTaxableGross,
                //'total_deductions' => $yearToDateTotalDeductions,
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