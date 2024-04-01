<?php

namespace Modules\Dashboard\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Modules\Payslip\Entities\Payslip;
use Modules\Payslip\Http\Resources\PayslipResource;

class ReportController extends Controller
{
    public function department_wise_report(Request $request)
    {
        $request->validate([
            'department_id' => 'required',
            'month' => 'required'
        ]);

        $payslips = Payslip::get();

        return PayslipResource::collection(
            $payslips
        );
    }

    public function get_internal_report(Request $request)
    {
        $response = [
            [
                'employee' => 'Anindha',
                'hours_worked' => 173.00,
                'wages' => 10000.00,
                'taxable_allowances' => 3000.00,
                'non_taxable_allowance' => 7000.00,
                'total_gross_pay' => 20000.00,
                'taxable_gross_pay' => 13000.00,
                'y_t_d_tax_paid' => 0.00,
                'total_staff_contribution' => 0.00,
                'total_company_contribution' => 0.00,
                'total_staff_cost' => 20000.00,
                'total_net_pay' => 20000.00
            ],
            [
                'employee' => 'Nashed',
                'hours_worked' => 143.00,
                'wages' => 10000.00,
                'taxable_allowances' => 3000.00,
                'non_taxable_allowance' => 7000.00,
                'total_gross_pay' => 20000.00,
                'taxable_gross_pay' => 13000.00,
                'y_t_d_tax_paid' => 0.00,
                'total_staff_contribution' => 0.00,
                'total_company_contribution' => 0.00,
                'total_staff_cost' => 20000.00,
                'total_net_pay' => 20000.00
            ],
            [
                'employee' => 'Shoriful',
                'hours_worked' => 103.00,
                'wages' => 10000.00,
                'taxable_allowances' => 3000.00,
                'non_taxable_allowance' => 7000.00,
                'total_gross_pay' => 20000.00,
                'taxable_gross_pay' => 13000.00,
                'y_t_d_tax_paid' => 0.00,
                'total_staff_contribution' => 0.00,
                'total_company_contribution' => 0.00,
                'total_staff_cost' => 20000.00,
                'total_net_pay' => 20000.00
            ],
        ];
        return apiResponse(
            data: $response
        );
    }
}
