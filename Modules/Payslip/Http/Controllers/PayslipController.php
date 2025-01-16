<?php

namespace Modules\Payslip\Http\Controllers;

use App\Models\DemoPayslip;
use App\Models\User;
use DateTime;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Modules\EmployeeSalaryItems\Entities\EmployeeSalaryItem;
use Modules\LeaveManagement\Entities\UserLeave;
use Modules\Payslip\Entities\Payslip;
use Modules\Payslip\Http\Resources\CategoryResource;
use Modules\Payslip\Http\Resources\PayslipResource;
use Modules\Payslip\Http\Resources\ViewFrenchPayslipResource;
use Modules\Payslip\Http\Resources\ViewUKPayslipResource;
use Modules\Payslip\Http\Resources\ViewUSAPayslipResource;
use Modules\Payslip\Http\Resources\ViewPayslipResource;
use Modules\Payslip\Http\Services\PayslipService;
use Modules\Payslip\Http\Jobs\DepartmentWisePayslipJob;
use Modules\Payslip\Http\Resources\LeavesDataResource;
use Modules\Payslip\Http\Resources\SalaryItemsResource;
use Modules\Payslip\Http\Resources\ViewAfricanPayslipResource;
use Modules\Payslip\Http\Resources\ViewIndianPayslipResource;
use Modules\Payslip\Notifications\PayslipCreatedNotificationToUser;
use Modules\Payslip\Repositories\Interfaces\PayslipRepositoryInterface;
use Modules\SalaryItemsCategory\Entities\SalaryItemsCategory;

class PayslipController extends Controller
{
    public function __construct(
        public PayslipService $payslipService,
        public PayslipRepositoryInterface $payslipRepositoryInterface
    ) {
    }

    public function request_for_payslip(Request $request)
    {
        $request->validate([
            'employee_id' => 'required',
            'from_date' => 'required|date|date_format:Y-m-d',
            'to_date' => 'required|date|date_format:Y-m-d',
            'payment_date' => 'required|date|date_format:Y-m-d',
        ]);

        return apiResponse(
            data: $request->all(),
            message: 'success',
            statusCode: 200
        );
    }

    public function leaves_data(Request $request)
    {
        $request->validate([
            'employee_id' => 'required',
            'from_date' => 'required|date|date_format:Y-m-d',
            'to_date' => 'required|date|date_format:Y-m-d'
        ]);

        $user = User::where('id', $request->employee_id)->firstOrFail();

        return apiResponse(
            data: new LeavesDataResource($user)
        );
    }

    /**
     * Get All Salary Items to an Employee Starts
     */
    public function employee_salary_items(Request $request)
    {
        $request->validate([
            'employee_id' => 'required',
            'from_date' => 'required|date|date_format:Y-m-d',
            'to_date' => 'required|date|date_format:Y-m-d',
            'payment_date' => 'required|date|date_format:Y-m-d',
        ]);
        $user = User::where('id', request('employee_id'))->first();

        return apiResponse(
            data: SalaryItemsResource::collection(
                $user->salary_items
            )
        );

        return apiResponse(
            data: $user->salary_items?->map(function ($s_items) {
                return [
                    'name' => $s_items->salaryItemsName?->name,
                    'hours_days' => $this->getHoursDaysCalculation($s_items),
                    'rate' => $this->getRateCalculation($s_items, $s_items->is_percentage),
                    'amount' => $this->getAmountCalculation($s_items),
                    'base' => $s_items->id // salary item id
                ];
            }),
            message: 'success',
            statusCode: 200
        );
    }

    function getHoursDaysCalculation($salary_item) {
        if($salary_item->salaryItemsName->name == "Wages"){
            if($salary_item->amount <= 0){
                $hours_worked = $this->payslipService->get_hours_worked(request('employee_id'), request('from_date'), request('to_date'));
                return $hours_worked . " hours";
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
        // if ($salary_item->is_percentage == 1) {
        //     // Assuming 'base_amount' is your gross pay or basic salary
        //     $base_amount = $this->getBaseAmountForPercentage($salary_item);
        //     return ($salary_item->amount / 100) * $base_amount;
        // }
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
                $hours_worked = $this->payslipService->get_hours_worked(request('employee_id'), request('from_date'), request('to_date'));
                if($hours_worked < 0){
                    $employee_associated_amount = 0;
                } else{
                    $employee_associated_amount = $hourly_amount->amount * $hours_worked;
                }
                return $employee_associated_amount;
            }
            return $salary_item->amount;
        }
        if($salary_item->salaryItemsName->salaryItemsCategory->id == 1){
            return 0;
        }
        if($salary_item->salaryItemsName->salaryItemsCategory->id == 2){
            return $salary_item->amount * $this->get_leave_details(request('employee_id'), $salary_item->salaryItemsName->id); // * no of leave days/hours
        }
        if($salary_item->salaryItemsName->salaryItemsCategory->id == 7 || $salary_item->salaryItemsName->salaryItemsCategory->id == 8){
            if($salary_item->is_percentage == 1)
            {
                return 'Emp: ' . $salary_item->deduction_details?->employee_amount . '%, Cmp_Or_Othrs: ' . $salary_item->deduction_details?->government_or_company_amount . '%';
            }
            return 'Emp: ' . $salary_item->deduction_details?->employee_amount . ', Cmp_Or_Othrs: ' . $salary_item->deduction_details?->government_or_company_amount;
        }
        if($salary_item->is_percentage == 1){
            return round($salary_item->amount / 100, 2);
        }
        return $salary_item->amount;
    }


    /**
     * Get All Salary Items to an Employee Ends
     */


    /**
     * Get Salary Items Category Calculation to an Employee Starts
     */
    public function employee_salary_items_calculation(Request $request)
    {
        $request->validate([
            'employee_id' => 'required',
            'from_date' => 'required|date|date_format:Y-m-d',
            'to_date' => 'required|date|date_format:Y-m-d',
            'payment_date' => 'required|date|date_format:Y-m-d',
        ]);
        $user = User::where('id', request('employee_id'))->first();
        $salary_category = SalaryItemsCategory::query()
                            // ->whereHas(
                            //     'salaryItemsName', fn(Builder $query) => $query
                            //         ->whereHas(
                            //             'employeeSalaryItem', fn(Builder $query) => $query
                            //                 ->where('company_id', auth()->user()->company_id)
                            //                 ->where('employee_id', $user->id)
                            //         )
                            // )
            ->get();

        return CategoryResource::collection(
            $salary_category
        )->additional([
            'meta' => [
                'total_pay' => $this->payslipService->getAmountForEmployee(1, $request->employee_id) - $this->payslipService->getAmountForEmployee(2, $request->employee_id),
                'category_one_other_values' => $this->payslipService->getCategoryOneOtherValues(1, $request->employee_id),
                'gross_pay_before_tax' => ($this->payslipService->getAmountForEmployee(1, $request->employee_id) - $this->payslipService->getAmountForEmployee(2, $request->employee_id)) + $this->payslipService->getAmountForEmployee(3, $request->employee_id),
                'gross_pay_after_tax' => (($this->payslipService->getAmountForEmployee(1, $request->employee_id) - $this->payslipService->getAmountForEmployee(2, $request->employee_id)) + $this->payslipService->getAmountForEmployee(3, $request->employee_id)) - ($this->payslipService->getAmountForEmployee(5, $request->employee_id) + $this->payslipService->getAmountForEmployee(6, $request->employee_id)),
                'pay_due_before_deduction' => ((($this->payslipService->getAmountForEmployee(1, $request->employee_id) - $this->payslipService->getAmountForEmployee(2, $request->employee_id)) + $this->payslipService->getAmountForEmployee(3, $request->employee_id)) - ($this->payslipService->getAmountForEmployee(5, $request->employee_id) + $this->payslipService->getAmountForEmployee(6, $request->employee_id))) + $this->payslipService->getAmountForEmployee(4, $request->employee_id),
                'net_pay' => (((($this->payslipService->getAmountForEmployee(1, $request->employee_id) - $this->payslipService->getAmountForEmployee(2, $request->employee_id)) + $this->payslipService->getAmountForEmployee(3, $request->employee_id)) - ($this->payslipService->getAmountForEmployee(5, $request->employee_id) + $this->payslipService->getAmountForEmployee(6, $request->employee_id))) + $this->payslipService->getAmountForEmployee(4, $request->employee_id)) - $this->payslipService->getAmountForEmployee(7, $request->employee_id),
            ],
        ]);
    }

    public function getCategoryOneOtherValues($employee_id)
    {
        $overtime = 0;
        if(request('overtime')){
            $overtime = (int)request('overtime') * $this->payslipService->getSalaryItemAmount("Overtime Rate")->amount;
        }
        $double_overtime = 0;
        if(request('double_overtime')){
            $double_overtime = (int)request('double_overtime') * $this->payslipService->getSalaryItemAmount("Double Overtime Rate")->amount;
        }
        $bonus = 0;
        if(request('bonus')){
            $bonus = (int)request('bonus') * $this->payslipService->getSalaryItemAmount("Bonus")->amount;
        }
        return $overtime + $double_overtime + $bonus;
    }


    public function run_payslip(Request $request)
    {
        $request->validate([
            'employee_id' => 'required',
            'from_date' => 'required|date_format:Y-m-d',
            'to_date' => 'required|date_format:Y-m-d',
            'payment_date' => 'required|date_format:Y-m-d',
        ]);

        $get_basic = $this->payslipService->get_basic_amount($request->employee_id);
        $get_category_one_other_values = $this->getCategoryOneOtherValues($request->employee_id);
        if($request->company_id){
            $get_staff_deduction_sick_absent = $this->payslipService->get_staff_deduction_sick_absent_amount($request->employee_id, $request->company_id);
        } else{
            $get_staff_deduction_sick_absent = $this->payslipService->get_staff_deduction_sick_absent_amount($request->employee_id, auth()->user()->company_id);
        }
        if($request->company_id){
            $get_staff_deduction_sick_absent = $this->payslipService->get_staff_deduction_sick_absent_amount($request->employee_id, $request->company_id);
        } else{
            $get_staff_deduction_sick_absent = $this->payslipService->get_staff_deduction_sick_absent_amount($request->employee_id, auth()->user()->company_id);
        }
        if($request->company_id){
            $get_taxable_allowance = $this->payslipService->get_taxable_allowance_amount($request->employee_id, $request->company_id);
        } else{
            $get_taxable_allowance = $this->payslipService->get_taxable_allowance_amount($request->employee_id, auth()->user()->company_id);
        }
        if($request->company_id){
            $get_non_taxable_allowance = $this->payslipService->get_non_taxable_allowance_amount($request->employee_id, $request->company_id);
        } else{
            $get_non_taxable_allowance = $this->payslipService->get_non_taxable_allowance_amount($request->employee_id, auth()->user()->company_id);
        }
        if($request->company_id){
            $get_income_taxes = $this->payslipService->get_income_taxes_amount($request->employee_id, $request->company_id);
        } else{
            $get_income_taxes = $this->payslipService->get_income_taxes_amount($request->employee_id, auth()->user()->company_id);
        }
        if($request->company_id){
            $get_additional_taxes_tax_top_up = $this->payslipService->get_additional_taxes_tax_top_up_amount($request->employee_id, $request->company_id);
        } else{
            $get_additional_taxes_tax_top_up = $this->payslipService->get_additional_taxes_tax_top_up_amount($request->employee_id, auth()->user()->company_id);
        }
        if($request->company_id){
            $get_government_deduction = $this->payslipService->government_deduction_amount($request->employee_id, $request->company_id); // employee deduction total
        } else{
            $get_government_deduction = $this->payslipService->government_deduction_amount($request->employee_id, auth()->user()->company_id); // employee deduction total
        }
        if($request->company_id){
            $get_other_complimentary_deduction = $this->payslipService->other_complimentary_deduction_amount($request->employee_id, $request->company_id); // company contribution total
        } else{
            $get_other_complimentary_deduction = $this->payslipService->other_complimentary_deduction_amount($request->employee_id, auth()->user()->company_id); // company contribution total
        }
        if($request->company_id){
            $get_company_contribution_value = $this->payslipService->company_contribution_value($request->employee_id, $request->company_id);
        } else{
            $get_company_contribution_value = $this->payslipService->company_contribution_value($request->employee_id, auth()->user()->company_id);
        }
        if($request->company_id){
            $get_employee_contribution_value = $this->payslipService->employee_contribution_value($request->employee_id, $request->company_id);
        } else{
            $get_employee_contribution_value = $this->payslipService->employee_contribution_value($request->employee_id, auth()->user()->company_id);
        }
        if($request->company_id){
            $get_other_company_deduction = $this->payslipService->other_company_deduction($request->employee_id, $request->company_id);
        } else{
            $get_other_company_deduction = $this->payslipService->other_company_deduction($request->employee_id, auth()->user()->company_id);
        }
        if($request->company_id){
            $get_other_company_contribution = $this->payslipService->other_company_contribution($request->employee_id, $request->company_id);
        } else{
            $get_other_company_contribution = $this->payslipService->other_company_contribution($request->employee_id, auth()->user()->company_id);
        }

        $total_pay = ($get_basic + $get_category_one_other_values) - $get_staff_deduction_sick_absent; // have to deduct unpaid leave from basic & other values
        $gross_pay_before_tax = $total_pay + $get_taxable_allowance; // have to add previous value with taxable allowance
        $gross_pay_after_tax = $gross_pay_before_tax - ($get_income_taxes + $get_additional_taxes_tax_top_up); // have to deduct (income tax & additional taxes tax top up) from previous value
        $pay_due_before_deductions = $gross_pay_after_tax + $get_non_taxable_allowance; // have to add previous value with non taxable allowance
        // $total_employee_contribution_for_deduction = $get_government_deduction + $get_other_complimentary_deduction; // employee contribution
        $total_employee_contribution_for_deduction = $get_government_deduction; // employee contribution
        $total_company_contribution_for_deduction = $get_government_deduction + $get_other_complimentary_deduction; // company contribution

        $total_amount = $pay_due_before_deductions - $total_employee_contribution_for_deduction;

        // GET HOURS WORKED
        $hours_worked = $this->payslipService->get_hours_worked($request->employee_id, $request->from_date, $request->to_date);

        // return [
        //     'basic' => $get_basic,
        //     'sick_absent' => $get_staff_deduction_sick_absent,
        //     'taxable_allowance' => $get_taxable_allowance,
        //     'non_taxable_allowance' => $get_non_taxable_allowance,
        //     'income_tax' => $get_income_taxes,
        //     'additional_tax' => $get_additional_taxes_tax_top_up,
        //     'government_deduction' => $get_government_deduction,
        //     'complimentary_deduction' => $get_other_complimentary_deduction,
        //     'total' => $total_amount,
        //     'hours_worked' => $hours_worked
        // ];
        // deduction value need to be re calculated. That's why committing this

        // $get_total_amount_except_basic_attendance = $this->payslipService->get_total_amount_except_basic_attendance($request->employee_id);
        /**
         * $get_total_deduction_amount_for_attendance = $this->payslipService->get_total_deduction_amount_for_attendance($request->employee_id);
         */
        $calculation = DB::transaction(function () use ($request, $get_basic, $get_staff_deduction_sick_absent, $total_pay, $get_taxable_allowance, $gross_pay_before_tax, $gross_pay_after_tax, $get_income_taxes, $get_additional_taxes_tax_top_up, $get_non_taxable_allowance, $pay_due_before_deductions, $total_amount, $hours_worked, $get_government_deduction, $get_other_complimentary_deduction, $get_company_contribution_value, $get_employee_contribution_value, $get_other_company_deduction, $get_other_company_contribution) {
            /** create payslip */
            $payslip = Payslip::create([
                'employee_id' => $request->employee_id,
                'company_id' => $request->company_id ?? auth()->user()->company_id,
                'month' => (new DateTime($request->from_date))->format('F'), // month name
                'amount' => $total_amount,
                'first_date' => $request->from_date,
                'last_date' => $request->to_date,
                'payment_date' => $request->payment_date,
                'hours_worked' => $hours_worked,
                'wages' => $get_basic,
                'leave_deduction' => $get_staff_deduction_sick_absent,
                'total_pay_value' => $total_pay,
                'taxable_allowance' => $get_taxable_allowance,
                'gross_pay_after_tax' => $gross_pay_after_tax,
                'gross_pay_before_tax' => $gross_pay_before_tax,
                'tax_value' => $get_income_taxes,
                'post_tax_value' => $get_additional_taxes_tax_top_up,
                'non_taxable_allowance' => $get_non_taxable_allowance,
                'pay_due_before_deduction' => $pay_due_before_deductions,
                'company_contribution_value' => $get_company_contribution_value,
                'employee_contribution_value' => $get_employee_contribution_value,
                'other_company_deduction' => $get_other_company_deduction,
                'other_company_contribution' => $get_other_company_contribution,
                'net_pay' => $total_amount,
                'total_employee_deduction' => $get_government_deduction,
                'company_contribution' => $get_other_complimentary_deduction
            ]);

            /** add the payslip details */
            if($request->company_id){
                $this->payslipService->add_payslip_details($payslip->id, $request->employee_id, $request->company_id);
            } else{
                $this->payslipService->add_payslip_details($payslip->id, $request->employee_id, auth()->user()->company_id);
            }
            /** notification to user */
            // $payslip->employee->notify(new PayslipCreatedNotificationToUser(auth()->user(), $payslip));

            return $payslip;
        });

        return apiResponse(
            data: null,
            message: 'Payslip Created Successfully',
            status: 'success',
            statusCode: 201
        );
    }

    function run_department_wise_payslip(Request $request) : JsonResponse
    {
        $request->validate([
            'department_id' => 'required|exists:departments,id',
            'from_date' => 'required|date_format:Y-m-d',
            'to_date' => 'required|date_format:Y-m-d',
            'payment_date' => 'required|date_format:Y-m-d',
        ]);
        DepartmentWisePayslipJob::dispatch(auth()->user(), $request->department_id, $request->from_date, $request->to_date, $request->payment_date);
        // AFTER COMPLETING THE JOB, HAVE TO SEND A NOTIFICATION
        return apiResponse(
            data: null,
            message: 'Department payslip running successfully! You\'ll be notified after completing all the payslips!'
        );
    }

    public function preview_payslip(Payslip $payslip)
    {
        $employee_type = [
            '0' => 'No Type',
            '1' => 'FULL TIME',
            '2' => 'PART TIME',
            '3' => 'FLEXI TIME',
            '4' => 'CONTRACTUAL'
        ];
        return apiResponse(
            data: [
                'user_info' => array_merge(
                    $payslip?->employee?->only(['name', 'email', 'customer_id']),
                    $payslip?->employee?->user_details?->only(['user_area', 'user_city','zip_code','gender','pension_number','visa_number','work_permit_number','uan_no','pf_no','esi_no','ni_category','national_identity_number','national_insurance_number','others_number','fax','passport','date_of_birth','bank_name','bank_bic_or_swift_code','bank_iban_or_account_no', 'state','region', 'user_phone', 'joining_date', 'social_security_number', 'tax_number']),
                    [
                        'employee_type' => $employee_type[$payslip?->employee?->employee_type ?? 0] ?? 'Unknown'
                    ],
                    [
                        'month' => $payslip?->month,
                        'pay_period' => $payslip?->first_date . " to " . $payslip?->last_date
                    ],
                    [
                        'department' => $payslip?->employee?->department?->department_name,
                        'designation' => $payslip?->employee?->designation?->name,
                    ]
                ),
                'company_info' => $payslip?->employee?->company?->only(['company_name', 'company_registration_no', 'company_email', 'government_employee_no', 'company_website', 'company_address']),
                'payslip_info' => new ViewPayslipResource($payslip), // THIS RESOURCE FILE SHOULD BE UPDATED WITH CORRECT DATA
            ]
        );
    }

    public function preview_french_payslip(Payslip $payslip)
    {
        return apiResponse(
            data: [
                'user_info' => array_merge(
                    $payslip?->employee->toArray(),
                    [
                        'department' => $payslip?->employee?->department?->department_name,
                        'designation' => $payslip?->employee?->designation?->name,
                    ]
                ),
                'company_info' => $payslip?->employee?->company,
                'payslip_info' => new ViewFrenchPayslipResource($payslip),
            ]
        );
    }

    public function preview_uk_payslip(Payslip $payslip)
    {
        $employee_type = [
            '0' => 'No Type',
            '1' => 'FULL TIME',
            '2' => 'PART TIME',
            '3' => 'FLEXI TIME',
            '4' =>'CONTRACTUAL'
        ];
        return apiResponse(
            data: [
                'user_info'=> array_merge(
                    $payslip?->employee?->only(['name', 'email', 'customer_id']),
                    $payslip?->employee?->user_details?->only(['user_area', 'user_city','zip_code','gender','pension_number','visa_number','work_permit_number','uan_no','pf_no','esi_no','ni_category','national_identity_number','national_insurance_number','others_number','fax','passport','date_of_birth','bank_name','bank_bic_or_swift_code','bank_iban_or_account_no', 'state','region', 'user_phone', 'joining_date', 'social_security_number', 'tax_number']),
                    [
                        'employee_type'=> $employee_type[$payslip?->employee?->employee_type ?? 0] ?? 'Unknown',
                    ],
                    [
                        'month' =>$payslip?->month,
                        'pay_period'=>$payslip?->first_date . 'to' . $payslip->last_date
                    ],
                    [
                        'department' => $payslip?->employee?->department?->department_name,
                        'designation' => $payslip?->employee?->designation?->name,
                    ]

                ),
                'company_info' => $payslip?->employee?->company?->only(['company_name', 'company_registration_no', 'company_email', 'government_employee_no', 'company_website', 'company_address']),
                'payslip_info' => new ViewUKPayslipResource($payslip),
                'others' => array_merge(
                    $payslip->only(['id', 'payment_date',])
                )
            ]
        );
    }

    public function preview_usa_payslip(Payslip $payslip)
    {
        $employee_type = [
            '0' => 'No Type',
            '1' => 'FULL TIME',
            '2' => 'PART TIME',
            '3' => 'FLEXI TIME',
            '4' =>'CONTRACTUAL'
        ];

        return apiResponse(
            data: [
                'user_info'=> array_merge(
                    $payslip?->employee?->only(['name', 'email', 'customer_id']),
                    $payslip?->employee?->user_details?->only(['user_area', 'user_city','zip_code','gender','pension_number','visa_number','work_permit_number','uan_no','pf_no','esi_no','ni_category','national_identity_number','national_insurance_number','others_number','fax','passport','date_of_birth','bank_name','bank_bic_or_swift_code','bank_iban_or_account_no', 'state','region', 'user_phone', 'joining_date', 'social_security_number', 'tax_number']),
                    [
                        'employee_type'=> $employee_type[$payslip?->employee?->employee_type ?? 0] ?? 'Unknown',
                    ],
                    [
                        'month' =>$payslip?->month,
                        'pay_period'=>$payslip?->first_date . 'to' . $payslip->last_date
                    ],
                    [
                        'department' => $payslip?->employee?->department?->department_name,
                        'designation' => $payslip?->employee?->designation?->name,
                    ]

                ),
                'company_info' => $payslip?->employee?->company?->only(['company_name', 'company_registration_no', 'company_email','company_phone','company_address', 'government_employee_no', 'company_website', 'company_address']),
                'payslip_info' => new ViewUSAPayslipResource($payslip),
                'others' => array_merge(
                    $payslip->only(['id', 'payment_date','created_at'])
                )
            ]
        );
    }

    public function preview_indian_payslip(Payslip $payslip)
    {
        $employee_type = [
            '0' => 'No Type',
            '1' => 'FULL TIME',
            '2' => 'PART TIME',
            '3' => 'FLEXI TIME',
            '4' => 'CONTRACTUAL'
        ];
        return apiResponse(
            data: [
                'user_info' => array_merge(
                    $payslip?->employee?->only(['name', 'email', 'customer_id']),
                    $payslip?->employee?->user_details?->only(['user_area', 'user_city','zip_code','tax_number','social_security_number','pension_number','visa_number','work_permit_number','ni_category','national_identity_number','national_insurance_number','others_number','fax','passport','bank_bic_or_swift_code','state','region', 'user_phone', 'joining_date','bank_name','bank_iban_or_account_no', 'esi_no', 'pf_no','uan_no']),
                    [
                        'employee_type' => $employee_type[$payslip?->employee?->employee_type ?? 0] ?? 'Unknown'
                    ],
                    [
                        'month' => $payslip?->month,
                        'year' =>$payslip->year,
                        'pay_period' => $payslip?->first_date . " to " . $payslip?->last_date
                    ],
                    [
                        'department' => $payslip?->employee?->department?->department_name,
                        'designation' => $payslip?->employee?->designation?->name,
                    ]
                ),
                'company_info' => $payslip?->employee?->company?->only(['company_name', 'company_address']),
                'payslip_info' => new ViewIndianPayslipResource($payslip), // THIS RESOURCE FILE SHOULD BE UPDATED WITH CORRECT DATA
                'others' => array_merge(
                    $payslip->only(['id', 'payment_date','net_pay'])
                )
            ]
        );
    }

    public function preview_african_payslip(Payslip $payslip)
    {
        $employee_type = [
            '0' => 'No Type',
            '1' => 'FULL TIME',
            '2' => 'PART TIME',
            '3' => 'FLEXI TIME',
            '4' => 'CONTRACTUAL'
        ];
        return apiResponse(
            data: [
                'user_info' => array_merge(
                    $payslip?->employee?->only(['name', 'email', 'customer_id']),
                    $payslip?->employee?->user_details?->only(['user_area', 'user_city','zip_code','gender','pension_number','visa_number','work_permit_number','uan_no','pf_no','esi_no','ni_category','national_identity_number','national_insurance_number','others_number','fax','passport','date_of_birth','bank_name','bank_bic_or_swift_code','bank_iban_or_account_no', 'state','region', 'user_phone', 'joining_date', 'social_security_number', 'tax_number']),
                    [
                        'employee_type' => $employee_type[$payslip?->employee?->employee_type ?? 0] ?? 'Unknown'
                    ],
                    [
                        'month' => $payslip?->month,
                        'pay_period' => $payslip?->first_date . " to " . $payslip?->last_date
                    ],
                    [
                        'department' => $payslip?->employee?->department?->department_name,
                        'designation' => $payslip?->employee?->designation?->name,
                    ]
                ),
                'company_info' => $payslip?->employee?->company?->only(['company_name', 'company_registration_no', 'company_email', 'government_employee_no', 'company_website', 'company_address']),
                'payslip_info' => new ViewAfricanPayslipResource($payslip), // THIS RESOURCE FILE SHOULD BE UPDATED WITH CORRECT DATA
            ]
        );
    }



    public function payslips()
    {

        $rows = 15;
        if (request()?->has('rows')) {
            $rows = (int) request('rows');
        }

        return PayslipResource::collection(
            $this->payslipRepositoryInterface->allWithSearch(
                ['*'],
                [
                    'employee',
                ],
                $rows
            )
        );
    }

    public function delete_payslip(Payslip $payslip, Request $request)
    {
        $payslip->delete();

        return apiResponse(
            data: null,
            message: 'Payslip Deleted Successfully'
        );
    }

    public function payslips_history(Request $request)
    {
        $request->validate([
            'employee_id' => 'required|exists:users,id',
            'from_date' => 'required|date_format:Y-m-d',
            'to_date' => 'required|date_format:Y-m-d',
        ]);
        $payslips = Payslip::query()
                    ->where('employee_id', $request->employee_id)
                    ->whereBetween('payment_date', [
                        $request->from_date,
                        $request->to_date
                    ])
                    ->get();

        return PayslipResource::collection(
            $payslips
        );
    }

    public function upload_own_design_payslip(Request $request)
    {
        $request->validate([
            'user_name' => 'required',
            'country_name' => 'required',
            'file' => 'required|mimes:png,jpeg,jpg,pdf,docx|max:2048',
        ]);

        try {
            $fileName = null;
            if($request->hasfile('file')){
                $authId = auth()->id();
                $fileName = $this->uploadAttachment($request, 'file', "demo_payslips/{$authId}/payslip");
            }
            DemoPayslip::create([
                'company_id' => auth()->id(),
                'user_name' => $request->user_name,
                'country_name' => $request->country_name,
                'file_name' => $fileName,
            ]);
            return apiResponse(
                data: null,
                message: 'Successfully Uploaded Payslip. Admin Will Get Back to you!'
            );
        }catch (\Exception $ex){
            return apiResponse(null, 'something went wrong', 403);
        }
    }

    private function uploadAttachment($request, $fileName=null,  $storagePath = '')
    {
        if($request->hasFile($fileName)){
            $file = $request->file($fileName);
            $uniqueFileName = rand(0, 999999999) . '_' . date('Ymdhis').'_' . rand(100, 999999999) . '.' . $file->getClientOriginalExtension();
            $file->storeAs($storagePath, $uniqueFileName, 'public');
            return "{$storagePath}/{$uniqueFileName}";
        }else{
            return false;
        }
    }
}
