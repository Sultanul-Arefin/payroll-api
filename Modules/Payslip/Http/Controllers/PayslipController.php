<?php

namespace Modules\Payslip\Http\Controllers;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Payslip\Http\Services\PayslipService;

class PayslipController extends Controller
{
    function __construct(
        public PayslipService $payslipService
    ) {
        
    }
    public function salary_information_before_running_payslip(Request $request)
    {
        $request->validate([
            'employee_id' => 'required'
        ]);
        $category_1 = $this->payslipService->getCategoryOneData($request->employee_id);
        return $category_1;
    }

    function run_payslip() {
        
    }

    function preview_payslip() {
        
    }
}
