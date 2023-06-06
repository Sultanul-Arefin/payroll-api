<?php

namespace Modules\Payslip\Http\Controllers;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class PayslipController extends Controller
{
    public function salary_information_before_running_payslip(Request $request)
    {
        return $request->all();
    }
}
