<?php

namespace Modules\Payslip\Http\Controllers;

use App\Models\User;
use DateTime;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Modules\LeaveManagement\Entities\UserLeave;
use Modules\Payslip\Entities\Payslip;
use Modules\Payslip\Http\Resources\CategoryResource;
use Modules\Payslip\Http\Resources\PayslipResource;
use Modules\Payslip\Http\Resources\ViewFrenchPayslipResource;
use Modules\Payslip\Http\Resources\ViewPayslipResource;
use Modules\Payslip\Http\Services\PayslipService;
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
            data: $user->salary_items?->map(function ($s_items) {
                return [
                    'name' => $s_items->salaryItemsName?->name,
                    'hours_days' => $this->getHoursDaysCalculation($s_items),
                    'rate' => $this->getRateCalculation($s_items),
                    'amount' => $this->getAmountCalculation($s_items)
                ];
            }),
            message: 'success',
            statusCode: 200
        );
    }

    function getHoursDaysCalculation($salary_item) {
        if($salary_item->salaryItemsName->name == "Wages"){
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

    function getRateCalculation($salary_item)
    {
        return $salary_item->amount;
    }

    function getAmountCalculation($salary_item)
    {
        if($salary_item->salaryItemsName->name == "Wages"){
            return $salary_item->amount;
        }
        if($salary_item->salaryItemsName->salaryItemsCategory->id == 1){
            return 0;
        }
        if($salary_item->salaryItemsName->salaryItemsCategory->id == 2){
            return $salary_item->amount * $this->get_leave_details(request('employee_id'), $salary_item->salaryItemsName->id); // * no of leave days/hours
        }
        if($salary_item->salaryItemsName->salaryItemsCategory->id == 7 || $salary_item->salaryItemsName->salaryItemsCategory->id == 8){
            return 'Emp: ' . $salary_item->deduction_details?->employee_amount . ', Cmp_Or_Othrs: ' . $salary_item->deduction_details?->government_or_company_amount;
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
                'gross_pay_before_tax' => ($this->payslipService->getAmountForEmployee(1, $request->employee_id) - $this->payslipService->getAmountForEmployee(2, $request->employee_id)) + $this->payslipService->getAmountForEmployee(3, $request->employee_id),
                'gross_pay_after_tax' => (($this->payslipService->getAmountForEmployee(1, $request->employee_id) - $this->payslipService->getAmountForEmployee(2, $request->employee_id)) + $this->payslipService->getAmountForEmployee(3, $request->employee_id)) - ($this->payslipService->getAmountForEmployee(5, $request->employee_id) + $this->payslipService->getAmountForEmployee(6, $request->employee_id)),
                'pay_due_before_deduction' => ((($this->payslipService->getAmountForEmployee(1, $request->employee_id) - $this->payslipService->getAmountForEmployee(2, $request->employee_id)) + $this->payslipService->getAmountForEmployee(3, $request->employee_id)) - ($this->payslipService->getAmountForEmployee(5, $request->employee_id) + $this->payslipService->getAmountForEmployee(6, $request->employee_id))) + $this->payslipService->getAmountForEmployee(4, $request->employee_id),
                'net_pay' => (((($this->payslipService->getAmountForEmployee(1, $request->employee_id) - $this->payslipService->getAmountForEmployee(2, $request->employee_id)) + $this->payslipService->getAmountForEmployee(3, $request->employee_id)) - ($this->payslipService->getAmountForEmployee(5, $request->employee_id) + $this->payslipService->getAmountForEmployee(6, $request->employee_id))) + $this->payslipService->getAmountForEmployee(4, $request->employee_id)) - $this->payslipService->getAmountForEmployee(7, $request->employee_id),
            ],
        ]);
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
        $get_staff_deduction_sick_absent = $this->payslipService->get_staff_deduction_sick_absent_amount($request->employee_id);
        $get_taxable_allowance = $this->payslipService->get_taxable_allowance_amount($request->employee_id);
        $get_non_taxable_allowance = $this->payslipService->get_non_taxable_allowance_amount($request->employee_id);
        $get_income_taxes = $this->payslipService->get_income_taxes_amount($request->employee_id);
        $get_additional_taxes_tax_top_up = $this->payslipService->get_additional_taxes_tax_top_up_amount($request->employee_id);
        $get_government_deduction = $this->payslipService->government_deduction_amount($request->employee_id); // employee deduction total
        $get_other_complimentary_deduction = $this->payslipService->other_complimentary_deduction_amount($request->employee_id); // company contribution total
        $get_company_contribution_value = $this->payslipService->company_contribution_value($request->employee_id);
        $get_employee_contribution_value = $this->payslipService->employee_contribution_value($request->employee_id);
        $get_other_company_deduction = $this->payslipService->other_company_deduction($request->employee_id);
        $get_other_company_contribution = $this->payslipService->other_company_contribution($request->employee_id);

        $total_pay = $get_basic - $get_staff_deduction_sick_absent; // have to deduct unpaid leave from basic
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

        // $get_total_amount_except_basic_attendance = $this->payslipService->get_total_amount_except_basic_attendance($request->employee_id);
        /**
         * $get_total_deduction_amount_for_attendance = $this->payslipService->get_total_deduction_amount_for_attendance($request->employee_id);
         */
        $calculation = DB::transaction(function () use ($request, $get_basic, $get_staff_deduction_sick_absent, $total_pay, $get_taxable_allowance, $gross_pay_before_tax, $gross_pay_after_tax, $get_income_taxes, $get_additional_taxes_tax_top_up, $get_non_taxable_allowance, $pay_due_before_deductions, $total_amount, $hours_worked, $get_government_deduction, $get_other_complimentary_deduction, $get_company_contribution_value, $get_employee_contribution_value, $get_other_company_deduction, $get_other_company_contribution) {
            /** create payslip */
            $payslip = Payslip::create([
                'employee_id' => $request->employee_id,
                'company_id' => auth()->user()->company_id,
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
            $payslip_details = $this->payslipService->add_payslip_details($payslip->id, $request->employee_id);

            /** notification to user */
            $payslip->employee->notify(new PayslipCreatedNotificationToUser(auth()->user(), $payslip));

            return $payslip;
        });

        return apiResponse(
            data: null,
            message: 'Payslip Created Successfully',
            status: 'success',
            statusCode: 201
        );
    }

    function run_department_wise_payslip(Request $request) {
        $request->validate([
            'department_id' => 'required|exists:departments,id',
            'from_date' => 'required|date_format:Y-m-d',
            'to_date' => 'required|date_format:Y-m-d',
            'payment_date' => 'required|date_format:Y-m-d',
        ]);
        return apiResponse(
            data: null,
            // message: 'Department payslip running successfully! You\'ll be notified after completing all the payslips!'
            message: 'This Module is Not Lived Yet! You\'ll be notified after completing!'
        );
    }

    public function preview_payslip(Payslip $payslip)
    {
        return apiResponse(
            data: [
                'user_info' => array_merge(
                    $payslip?->employee?->only(['name', 'email', 'customer_id', 'user_phone', 'user_city', 'employee_type', 'joining_date', ]),
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
}
