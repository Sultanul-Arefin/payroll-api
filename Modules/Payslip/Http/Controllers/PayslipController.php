<?php

namespace Modules\Payslip\Http\Controllers;

use App\Models\User;
use DateTime;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
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
        if($salary_item->salaryItemsName->salaryItemsCategory->id == 1 || $salary_item->salaryItemsName->salaryItemsCategory->id == 2){
            return 0; // this will come from no. of leave days
        }
        return "1 month";
    }

    function getRateCalculation($salary_item) {
        return $salary_item->amount;
    }

    function getAmountCalculation($salary_item) {
        if($salary_item->salaryItemsName->name == "Wages"){
            return $salary_item->amount;
        }
        if($salary_item->salaryItemsName->salaryItemsCategory->id == 1 || $salary_item->salaryItemsName->salaryItemsCategory->id == 2){
            return $salary_item->amount * 0; // * no of leave days/hours
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
                'net_pay' => ((($this->payslipService->getAmountForEmployee(1, $request->employee_id) - $this->payslipService->getAmountForEmployee(2, $request->employee_id)) + $this->payslipService->getAmountForEmployee(3, $request->employee_id)) - ($this->payslipService->getAmountForEmployee(5, $request->employee_id) + $this->payslipService->getAmountForEmployee(6, $request->employee_id))) + $this->payslipService->getAmountForEmployee(4, $request->employee_id),
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
        $get_government_deduction = $this->payslipService->government_deduction_amount($request->employee_id);
        $get_other_complimentary_deduction = $this->payslipService->other_complimentary_deduction_amount($request->employee_id);

        $total_pay = $get_basic - $get_staff_deduction_sick_absent; // have to deduct unpaid leave from basic
        $gross_pay_before_tax = $total_pay + $get_taxable_allowance; // have to add previous value with taxable allowance
        $gross_pay_after_tax = $gross_pay_before_tax - ($get_income_taxes + $get_additional_taxes_tax_top_up); // have to deduct (income tax & additional taxes tax top up) from previous value
        $pay_due_before_deductions = $gross_pay_after_tax + $get_non_taxable_allowance; // have to add previous value with non taxable allowance
        $total_employee_contribution_for_deduction = $get_government_deduction + $get_other_complimentary_deduction; // employee contribution
        $total_company_contribution_for_deduction = $get_government_deduction + $get_other_complimentary_deduction; // company contribution

        $total_amount = $pay_due_before_deductions - $total_employee_contribution_for_deduction;

        // return [
        //     'a' => $get_basic,
        //     'b' => $get_staff_deduction_sick_absent,
        //     'c' => $get_taxable_allowance,
        //     'd' => $get_non_taxable_allowance,
        //     'e' => $get_income_taxes,
        //     'f' => $get_additional_taxes_tax_top_up,
        //     'g' => $get_government_deduction,
        //     'h' => $get_other_complimentary_deduction
        // ];

        // $get_total_amount_except_basic_attendance = $this->payslipService->get_total_amount_except_basic_attendance($request->employee_id);
        /**
         * $get_total_deduction_amount_for_attendance = $this->payslipService->get_total_deduction_amount_for_attendance($request->employee_id);
         */
        $calculation = DB::transaction(function () use ($request, $get_basic, $get_staff_deduction_sick_absent, $total_pay, $get_taxable_allowance, $gross_pay_before_tax, $get_income_taxes, $get_additional_taxes_tax_top_up, $get_non_taxable_allowance, $pay_due_before_deductions, $total_amount) {
            /** create payslip */
            $payslip = Payslip::create([
                'employee_id' => $request->employee_id,
                'company_id' => auth()->user()->company_id,
                'month' => (new DateTime($request->from_date))->format('F'), // month name
                'amount' => $total_amount,
                'first_date' => $request->from_date,
                'last_date' => $request->to_date,
                'payment_date' => $request->payment_date,
                'hours_worked' => 148,
                'wages' => $get_basic,
                'leave_decution' => $get_staff_deduction_sick_absent,
                'total_pay_value' => $total_pay,
                'taxable_allowance' => $get_taxable_allowance,
                'gross_pay_before_tax' => $gross_pay_before_tax,
                'tax_value' => $get_income_taxes,
                'post_tax_value' => $get_additional_taxes_tax_top_up,
                'non_taxable_allowance' => $get_non_taxable_allowance,
                'pay_deduction' => $pay_due_before_deductions,
                'net_pay' => $total_amount,
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
            message: 'Department payslip running successfully! You\'ll be notified after completing all the payslips!'
        );
    }

    public function preview_payslip(Payslip $payslip)
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
                'payslip_info' => new ViewPayslipResource($payslip),
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
}
