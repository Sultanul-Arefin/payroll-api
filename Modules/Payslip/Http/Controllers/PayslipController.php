<?php

namespace Modules\Payslip\Http\Controllers;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Payslip\Http\Resources\SalaryItemsCategoryResource;
use Modules\Payslip\Http\Services\PayslipService;
use Modules\Payslip\Repositories\Interfaces\PayslipRepositoryInterface;

class PayslipController extends Controller
{
    function __construct(
        public PayslipService $payslipService,
        public PayslipRepositoryInterface $payslipRepositoryInterface
    ) {
        
    }
    public function salary_information_before_running_payslip(Request $request)
    {
        $request->validate([
            'employee_id' => 'required'
        ]);
        $rows = 15;
        if(request()?->has('rows')){
            $rows = (int) request('rows');
        }
        return SalaryItemsCategoryResource::collection(
            $this->payslipRepositoryInterface->getSalaryItemsCategory(
                ['*'],
                [],
                $rows
            )
        );
    }

    function run_payslip(Request $request) {
        $request->validate([
            'employee_id' => 'required',
            'from_date' => 'required',
            'to_date' => 'required'
            'payment_date' => 'required'
        ]);
    }

    function preview_payslip() {
        
    }
}
