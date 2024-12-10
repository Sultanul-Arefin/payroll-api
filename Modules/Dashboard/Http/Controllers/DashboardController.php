<?php

namespace Modules\Dashboard\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Modules\Company\Entities\AnnualHoliday;
use Modules\Dashboard\Http\Resources\HolidayResource;
use Modules\Department\Entities\Department;
use Modules\LeaveManagement\Entities\UserLeave;
use Modules\Payslip\Entities\Payslip;

class DashboardController extends Controller
{
    /**
     * all employees count under a company
     */
    public function totalEmployee(): JsonResponse
    {
        $data = User::where('company_id', auth()->user()->company_id)
            ->where('status', User::USER_ACTIVE)
            ->where('staff_interaction_panel_status', User::STAFF_INTERACTION_PANEL_GIVEN)
            ->where('role_id', '!=', User::ADMIN)
            ->selectRaw('COUNT(*) as count')
            ->first();

        return response()->json(['data' => $data]);
    }

    /**
     * all department count under a company
     */
    public function totalDepartment(): JsonResponse
    {
        $data = Department::where('company_id', auth()->user()->company_id)
            ->selectRaw('COUNT(*) as count')
            ->first();
        return response()->json(['data' => $data]);
    }

    public function holiday()
    {
        $holidays = AnnualHoliday::where('company_id', auth()->user()->company_id)->get();
        return HolidayResource::collection($holidays);
    }

    public function total_paid_salary()
    {
        $data = Payslip::where('company_id', auth()->user()->company_id)
            ->selectRaw('ROUND(SUM(total_pay_value), 2) as total_paid_salary')
            ->first();
        return response()->json(['data' => $data]);
    }

    public function total_staff_cost()
    {
        $data = Payslip::where('company_id', auth()->user()->company_id)
            ->selectRaw('ROUND(SUM(gross_pay_before_tax), 2) as total_staff_cost')
            ->first();

        return response()->json(['data' => $data]);
    }

    public function last_12_months_data()
    {
        $last12MonthsData = DB::table('payslips')
            ->select(
                'month as month_name',
                DB::raw('SUM(total_pay_value) as total_gross_pay'),
                DB::raw('SUM(gross_pay_before_tax) as total_staff_cost')
            )
            ->whereBetween('payment_date', [
                now()->subMonths(11)->startOfMonth(),  // Start of 12 months ago
                now()->startOfMonth(),  // Start of the current month
            ])
            ->where('company_id', auth()->user()->company_id)
            ->groupBy('payment_date')
            ->get();

        return response()->json([
            'data' => $last12MonthsData,
        ]);
    }

    function get_recent_leaves() {
        $leaves = UserLeave::query()
                ->get(); // Need to get the most recent leaves from today [ have to update the query here ]
        return apiResponse(
            data: $leaves,
            message: 'Success',
            status: 'success',
            statusCode: 200
        );
    }

    function get_others() {
        return apiResponse(
            data: null,
            message: 'Success',
            statusCode: 200
        );
    }
}
