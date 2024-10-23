<?php

namespace Modules\Dashboard\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Mail;
use PDF;
use GuzzleHttp\Promise\Create;
use Illuminate\Http\Request;
use Modules\Dashboard\app\Models\DedicatedDigitalTaxReport;
use Modules\Dashboard\app\Models\DedicatedDigitalSocialReport;
use Modules\Dashboard\app\Transformers\DigitalTaxReportResource;
use Modules\Payslip\Entities\Payslip;
use Modules\Payslip\Http\Resources\PayslipResource;
use App\Http\Traits\Attachment;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;

class ReportController extends Controller
{
    use Attachment;
    public function department_wise_report(Request $request)
    {
        $request->validate([
            'department_id' => 'required',
            'month' => 'required'
        ]);

        $payslips = Payslip::query()
                    ->whereHas(
                        'employee', function(Builder $builder) use($request){
                            $builder->where('department_id', $request->department_id);
                        }
                    )
                    ->where('month', 'like', '%' . $request->month . '%')
                    ->get();

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

    public function create_digital_tax_report(Request $request)
    {
        $request->validate([
            'email' => 'required_if:is_mailable,1|email',
            'month' => 'required',
            'year' => 'required',
            'is_mailable' => 'required|in:0,1',
            'ftp_host' => 'required_if:is_mailable,0',
            'ftp_username' => 'required_if:is_mailable,0',
            'ftp_password' => 'required_if:is_mailable,0'
        ]);
       $reports = Payslip::where('company_id', auth()->user()->company->id)
           ->where('month', $request->month)
           ->whereYear('first_date', $request->year)
           ->with('employee', 'employee.user_details')
           ->get();
       $data = [
            'result' => DigitalTaxReportResource::collection($reports),
            'tax_data' => [
                'month' => $request->month,
                'year' => $request->year,
                'email' => $request->email,
                'is_mailable' => $request->is_mailable,
                'ftp_data' => $request->ftp_host ? [
                    'ftp_host' => $request->ftp_host,
                    'ftp_username' => $request->ftp_username,
                    'ftp_password' => $request->ftp_password
                ] : null
            ]
        ];
        return apiResponse(
            data: $data
        );
    }

    public function send_digital_tax_report(Request $request)
    {
        $request->validate([
            'email' => 'required_if:is_mailable,1|email',
            'month' => 'required',
            'year' => 'required',
            'is_mailable' => 'required|in:0,1',
            'ftp_host' => 'required_if:is_mailable,0',
            'ftp_username' => 'required_if:is_mailable,0',
            'ftp_password' => 'required_if:is_mailable,0'
        ]);
        $reports = Payslip::where('company_id', auth()->user()->company->id)
            ->where('month', $request->month)
            ->whereYear('first_date', $request->year)
            ->with('employee', 'employee.user_details')
            ->get();
        $data = [
            'data' => $reports
        ];
        $pdf = PDF::loadView('reports.digital_tax_report', $data);
        $options = $pdf->getOptions();
        $options->set('defaultPaperSize', 'A4');
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true);

        if($request->is_mailable == 0){
            // Save the PDF to storage (e.g., storage/app/reports)
            $path = 'reports/digital_tax_report_' . time() . '.pdf'; // Generate a unique name for the file
            Storage::disk('public')->put($path, $pdf->output());

            // Get the full path to the saved file
            $localFilePath = Storage::disk('public')->path($path); // Full path in local storage

            // FTP server details
            $ftp_server = $request->ftp_host ?? "tellpe.com";
            $ftp_username = $request->ftp_username ?? "payroll-ftp";
            $ftp_password = $request->ftp_password ?? "*4u3f1R4j";

            // Connect and login to FTP server
            $ftp_conn = ftp_connect($ftp_server);
            if (!$ftp_conn) {
                return apiResponse(
                    data: null,
                    message: 'Couldn\'t connect to FTP Server! Please, Try Again!'
                );
            }
            $login = ftp_login($ftp_conn, $ftp_username, $ftp_password);

            // Check if login was successful
            if (!$login) {
                return apiResponse(
                    data: null,
                    message: 'FTP Login Failed! Please, Try Again!'
                );
            }

            // Remote file path on the FTP server
            $remoteFile = time() . ".pdf";  // Change directory and filename as needed

            // Upload the file to the FTP server
            if (ftp_put($ftp_conn, $remoteFile, $localFilePath, FTP_BINARY)) {
                // echo "Successfully uploaded {$path}.";
            } else {
                return apiResponse(
                    data: null,
                    message: 'FTP Upload Failed! Please, Try Again!'
                );
            }

            // Close FTP connection
            ftp_close($ftp_conn);

            return apiResponse(
                data: null,
                message: 'File Sent to FTP Server'
            );
        }


       // return $pdf->download('digital_tax_report.pdf');
        Mail::send('reports.digital_tax_report', $data, function($message) use($pdf, $request) {
            $message->to($request->email)
                ->subject('Digital Tax Report')
                ->attachData($pdf->output(), "digital_tax_report.pdf");
        });
        return apiResponse(
            data: null,
            message: 'Email send successfully!'
        );
    }

    public function upload_dedicated_digital_tax_report(Request $request)
    {
        $request->validate([
            'user_name' => 'required',
            'country_name' => 'required',
            'file_name' => 'required|mimes:png,jpeg,jpg,pdf,docx|max:2048',
        ]);

        try {
            $fileName = null;
            if($request->hasfile('file_name')){
                $authId = auth()->id();
                $fileName = $this->uploadAttachment($request, 'file_name', "reports/{$authId}/tax_report");
            }
            DedicatedDigitalTaxReport::create([
                'company_id' => auth()->id(),
                'user_name' => $request->user_name,
                'country_name' => $request->country_name,
                'file_name' => $fileName,
            ]);
            return apiResponse('', 'success', 200);
        }catch (\Exception $ex){
            return apiResponse(null, 'something went wrong', 403);
        }
    }

    public function create_digital_social_report(Request $request)
    {
        $request->validate([
            'email' => 'required_if:is_mailable,1|email',
            'month' => 'required',
            'year' => 'required',
            'is_mailable' => 'required|in:0,1',
            'ftp_host' => 'required_if:is_mailable,0',
            'ftp_username' => 'required_if:is_mailable,0',
            'ftp_password' => 'required_if:is_mailable,0'
        ]);
       $reports = Payslip::where('company_id', auth()->user()->company->id)
           ->where('month', $request->month)
           ->whereYear('first_date', $request->year)
           ->with('employee', 'employee.user_details')
           ->get();
       $data = [
            'result' => [
                [
                    'employee_name' => 'Test 1',
                    'details' => 'test details',
                    'staff_contribution' => 0000,
                    'company_contribution' => 0000,
                    'combined_contribution' => 00000
                ],
                [
                    'employee_name' => 'Test 1',
                    'details' => 'test details',
                    'staff_contribution' => 0000,
                    'company_contribution' => 0000,
                    'combined_contribution' => 00000
                ],
                [
                    'employee_name' => 'Test 1',
                    'details' => 'test details',
                    'staff_contribution' => 0000,
                    'company_contribution' => 0000,
                    'combined_contribution' => 00000
                ],
            ],
            'tax_data' => [
                'month' => $request->month,
                'year' => $request->year,
                'email' => $request->email,
                'is_mailable' => $request->is_mailable,
                'ftp_data' => $request->ftp_host ? [
                    'ftp_host' => $request->ftp_host,
                    'ftp_username' => $request->ftp_username,
                    'ftp_password' => $request->ftp_password
                ] : null
            ]
        ];
        return apiResponse(
            data: $data
        );
    }

    public function send_digital_social_report(Request $request)
    {
        $request->validate([
            'email' => 'required_if:is_mailable,1|email',
            'month' => 'required',
            'year' => 'required',
            'is_mailable' => 'required|in:0,1',
            'ftp_host' => 'required_if:is_mailable,0',
            'ftp_username' => 'required_if:is_mailable,0',
            'ftp_password' => 'required_if:is_mailable,0'
        ]);
        $reports = Payslip::where('company_id', auth()->user()->company->id)
            ->where('month', $request->month)
            ->whereYear('first_date', $request->year)
            ->with('employee', 'employee.user_details')
            ->get();
        $data = [
            'data' => $reports
        ];
        $pdf = PDF::loadView('reports.digital_tax_report', $data);
        $options = $pdf->getOptions();
        $options->set('defaultPaperSize', 'A4');
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true);

        if($request->is_mailable == 0){
            // Save the PDF to storage (e.g., storage/app/reports)
            $path = 'reports/digital_tax_report_' . time() . '.pdf'; // Generate a unique name for the file
            Storage::disk('public')->put($path, $pdf->output());

            // Get the full path to the saved file
            $localFilePath = Storage::disk('public')->path($path); // Full path in local storage

            // FTP server details
            $ftp_server = $request->ftp_host ?? "tellpe.com";
            $ftp_username = $request->ftp_username ?? "payroll-ftp";
            $ftp_password = $request->ftp_password ?? "*4u3f1R4j";

            // Connect and login to FTP server
            $ftp_conn = ftp_connect($ftp_server) or die("Could not connect to $ftp_server");
            $login = ftp_login($ftp_conn, $ftp_username, $ftp_password);

            // Check if login was successful
            if (!$login) {
                return apiResponse(
                    data: null,
                    message: 'FTP Login Failed! Please, Try Again!'
                );
            }

            // Remote file path on the FTP server
            $remoteFile = time() . ".pdf";  // Change directory and filename as needed

            // Upload the file to the FTP server
            if (ftp_put($ftp_conn, $remoteFile, $localFilePath, FTP_BINARY)) {
                // echo "Successfully uploaded {$path}.";
            } else {
                return apiResponse(
                    data: null,
                    message: 'FTP Upload Failed! Please, Try Again!'
                );
            }

            // Close FTP connection
            ftp_close($ftp_conn);

            return apiResponse(
                data: null,
                message: 'File Sent to FTP Server'
            );
        }

       // return $pdf->download('digital_tax_report.pdf');
        Mail::send('reports.digital_tax_report', $data, function($message) use($pdf, $request) {
            $message->to($request->email)
                ->subject('Digital Tax Report')
                ->attachData($pdf->output(), "digital_tax_report.pdf");
        });
        return apiResponse(
            data: null,
            message: 'Email send successfully!'
        );
    }

    public function upload_dedicated_digital_social_report(Request $request)
    {
        $request->validate([
            'user_name' => 'required',
            'country_name' => 'required',
            'file_name' => 'required|mimes:png,jpeg,jpg,pdf,docx|max:2048',
        ]);
        try {
            $fileName = null;
            if($request->hasfile('file_name')){
                $authId = auth()->id();
                $fileName = $this->uploadAttachment($request, 'file_name', "reports/{$authId}/social_report");
            }
            DedicatedDigitalSocialReport::create([
                'company_id' => auth()->id(),
                'user_name' => $request->user_name,
                'country_name' => $request->country_name,
                'file_name' => $fileName,
            ]);
            return apiResponse('', 'success', 200);
        }catch (\Exception $ex){
            return apiResponse(null, 'something went wrong', 403);
        }
    }
}
