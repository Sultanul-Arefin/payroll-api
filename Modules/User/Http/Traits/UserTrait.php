<?php

namespace Modules\User\Http\Traits;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

trait UserTrait
{
    public function user_info_for_payslip(Request $request)
    {
        $request->validate([
            'employee_id' => 'required',
        ]);

        $user = User::where('id', $request->employee_id)->select('name')->first();

        return apiResponse(
            data: $user,
            message: 'User Get Successfully!',
            status: 'success',
            statusCode: 200
        );
    }

    public function activate_user(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'role_id' => 'required|integer|in:1,2,3',
            'password' => 'required|string|max:30',
        ]);
        $update_user = User::where('id', $request->user_id)->update([
            'role_id' => $request->role_id,
            'status' => User::USER_ACTIVE,
            'password' => Hash::make($request->password),
            'staff_interaction_panel_status' => User::STAFF_INTERACTION_PANEL_GIVEN
        ]);

        return apiResponse(
            data: null,
            message: 'User Successfully Updated'
        );
    }

    public function update_password(User $user, Request $request)
    {
        $request->validate([
            'password' => 'required|string|max:30',
            '_method' => 'required'
        ]);
        $user->update([
            'password' => Hash::make($request->password)
        ]);

        return apiResponse(
            data: null,
            message: 'User Password Successfully Updated'
        );
    }

    public function update_role(User $user, Request $request)
    {
        $request->validate([
            'role_id' => 'required|integer|in:1,2,3'
        ]);
        $updated_user = $user->update([
            'role_id' => $request->role_id
        ]);
        return apiResponse(
            data: null,
            message: 'User Role Successfully Updated'
        );
    }

    public function user_information()
    {
        if (auth()->user()->company->associated_package->package->id == '1' || auth()->user()->company->associated_package->package->id == '2' || auth()->user()->company->associated_package->package->id == '3' || auth()->user()->company->associated_package->package->id == '15' || auth()->user()->company->associated_package->package->id == '16' || auth()->user()->company->associated_package->package->id == '17' || auth()->user()->company->associated_package->package->id == '18' || auth()->user()->company->associated_package->package->id == '19' || auth()->user()->company->associated_package->package->id == '20' || auth()->user()->company->associated_package->package->id == '21' || auth()->user()->company->associated_package->package->id == '22') {
            $company_image = env('APP_URL').'/storage/uploads/company/logo/logo-payroll.png';
        } elseif (auth()->user()->company->associated_package->package->id == '14') {
            $company_image = env('APP_URL').'/storage/uploads/company/logo/SOFTDRIVE-EASYBOOKS.png';
        } elseif (auth()->user()->company->associated_package->package->id == '23' || auth()->user()->company->associated_package->package->id == '24' || auth()->user()->company->associated_package->package->id == '25' || auth()->user()->company->associated_package->package->id == '26' || auth()->user()->company->associated_package->package->id == '27') {
            $company_image = env('APP_URL').'/storage/uploads/company/logo/Project-Manager.jpg';
        } else {
            $company_image = env('APP_URL').'/storage/uploads/company/logo/HR-Payroll.jpg';
        }

        return apiResponse(
            data: [
                'company_image' => auth()->user()->company->company_logo ? env('APP_URL').'/'.'storage/'.auth()->user()->company->company_logo : $company_image,
                'user_image' => auth()->user()->user_image ? env('APP_URL').'/'.'storage/'.auth()->user()->user_image : $company_image,
                'packageId' => auth()->user()->company->associated_package->package_id,
                'role' => auth()->user()->role_id,
            ]
        );
    }

    function terminate_employee(Request $request) {
        $request->validate([
            'employee_id' => 'required|integer|exists:users,id'
        ]);
        $user = User::where('id', $request->employee_id)->first();
        if($user->payslips->count() == 0)
        {
            return apiResponse(
                data: [],
                message: 'No Payslips Found! Please Create Payslip First',
                status: 'error',
                statusCode: 404
            );
        }

        $payslips = $user->payslips; // GET THE PAYSLIPS ASSOCIATED WITH THIS USER

        // PAYSLIP REPORT
        $payslip_report = array();
        foreach($payslips as $payslip)
        {
            array_push($payslip_report, [
                'payment_date' => $payslip->payment_date,
                'month' => $payslip->month,
                'gross_pay' => $payslip->wages,
                'fixed_pay' => $payslip->net_pay
            ]);
        }

        // OTHERS REPORT
        $other_report = array();
        foreach($payslips as $payslip)
        {
            array_push($other_report, [
                'month' => $payslip->payment_date,
                'gross_pay' => $payslip->wages,
                'fixed_pay' => $payslip->net_pay,
                'allowance' => $payslip->non_taxable_allowance,
                'ordinary_time' => $payslip->hours_worked,
                'recuperated_hour' => 0,
                'maternity_leave' => 0,
                'holiday' => 0,
                'sick_leave' => 0,
                'overtime' => 0,
                'bonus' => 0,
                'double_overtime' => 0
            ]);
        }
        return apiResponse(
            data: [
                'attestation_letter' => [
                    'date' => date('d-m-Y'),
                    'employee_name' => $user->name,
                    'company_name' => $user->company->company_name,
                    'joining_date' => date('m-d-Y', strtotime($user->user_details?->joining_date)),
                    'termination_date' => date('d-m-Y'),
                    'designation' => $user->designation?->name,
                    'payslip_report' => $payslip_report,
                    'other_report' => $other_report
                ],
                'termination_letter' => [
                    'date' => date('d-m-Y'),
                    'employee_name' => $user->name,
                    'contacted_email' => auth()->user()->email,
                    'contacted_name' => auth()->user()->name
                ]
            ]
        );
    }
}
