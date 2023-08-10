<?php

namespace Modules\Payslip\Http\Controllers;

use App\Models\User;
use DateTime;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Modules\Payslip\Entities\Payslip;
use Modules\Payslip\Entities\PayslipDetail;
use Modules\Payslip\Http\Resources\CategoryResource;
use Modules\Payslip\Http\Resources\PayslipResource;
use Modules\Payslip\Http\Resources\SalaryItemsCategoryResource;
use Modules\Payslip\Http\Services\PayslipService;
use Modules\Payslip\Repositories\Interfaces\PayslipRepositoryInterface;
use Modules\SalaryItemsCategory\Entities\SalaryItemsCategory;

class PayslipController extends Controller
{
    function __construct(
        public PayslipService $payslipService,
        public PayslipRepositoryInterface $payslipRepositoryInterface
    ) {}

    function request_for_payslip(Request $request) {
        $request->validate([
            'employee_id' => 'required',
            'from_date' => 'required|date|date_format:Y-m-d',
            'to_date' => 'required|date|date_format:Y-m-d',
            'payment_date' =>  'required|date|date_format:Y-m-d'
        ]);

        return apiResponse(
            data: $request->all(),
            message: 'success',
            statusCode: 200
        );
    }

    function employee_salary_items(Request $request) {
        $request->validate([
            'employee_id' => 'required'
        ]);
        $user = User::where('id', request('employee_id'))->first();

        return apiResponse(
            data: $user->salary_items?->map(function($s_items){
                return [
                    'name' => $s_items->salaryItemsName?->name,
                    'value' => $s_items->amount
                ];
            }),
            message: 'success',
            statusCode: 200
        );
    }

    function employee_salary_items_calculation(Request $request) {
        $request->validate([
            'employee_id' => 'required'
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
        );
    }

    function run_payslip(Request $request) {
        $request->validate([
            'employee_id'   => 'required',
            'from_date'     => 'required|date_format:Y-m-d',
            'to_date'       => 'required|date_format:Y-m-d',
            'payment_date'  => 'required|date_format:Y-m-d'
        ]);

        $get_total_amount_except_basic_attendance = $this->payslipService->get_total_amount_except_basic_attendance($request->employee_id);
        /** 
         * $get_total_deduction_amount_for_attendance = $this->payslipService->get_total_deduction_amount_for_attendance($request->employee_id);
         */

        $calculation = DB::transaction(function() use($request, $get_total_amount_except_basic_attendance){
            /** create payslip */
            $payslip = Payslip::create([
                'employee_id' => $request->employee_id,
                'company_id' => auth()->user()->company_id,
                'month' => (new DateTime($request->from_date))->format('F'), // month name
                'amount' => $get_total_amount_except_basic_attendance,
                'first_date' => $request->from_date,
                'last_date' => $request->to_date,
                'payment_date' => $request->payment_date,
                'hours_worked' => 148
            ]);

            /** add the payslip details */
            $payslip_details = $this->payslipService->add_payslip_details($payslip->id, $request->employee_id);
            return $payslip;
        });

        return apiResponse(
            data: null,
            message: 'Payslip Created Successfully',
            status: 'success',
            statusCode: 201
        );
    }

    function preview_payslip() {
        
    }

    function payslips() {

        $rows = 15;
        if(request()?->has('rows')){
            $rows = (int) request('rows');
        }

        return PayslipResource::collection(
            $this->payslipRepositoryInterface->allWithSearch(
                ['*'],
                [
                    'employee'
                ],
                $rows
            )
        );
    }
}
