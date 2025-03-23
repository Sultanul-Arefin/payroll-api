<?php

namespace Modules\Dashboard\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Mail;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;
use Illuminate\Support\Facades\View;
use PDF;
use GuzzleHttp\Promise\Create;
use Illuminate\Http\Request;
use Modules\Dashboard\app\Models\DedicatedDigitalTaxReport;
use Modules\Dashboard\app\Models\DedicatedDigitalSocialReport;
use Modules\Dashboard\app\Transformers\DigitalTaxReportResource;
use Modules\Payslip\Entities\Payslip;
use Modules\Payslip\Http\Resources\PayslipResource;
use App\Http\Traits\Attachment;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;
use Modules\Dashboard\app\Transformers\DigitalSocialReportResource;

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
            'is_mailable' => 'required|in:0,1,2', /* mail=>1, ftp=>0, dsn=>2 */
            'ftp_host' => 'required_if:is_mailable,0',
            'ftp_username' => 'required_if:is_mailable,0',
            'ftp_password' => 'required_if:is_mailable,0',
            'dsn_link' => 'required_if:is_mailable,2',
            'dsn_username' => 'required_if:is_mailable,2',
            'dsn_password' => 'required_if:is_mailable,2'
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
                'ftp_data' => $request->is_mailable == 0 ? [
                    'ftp_host' => $request->ftp_host,
                    'ftp_username' => $request->ftp_username,
                    'ftp_password' => $request->ftp_password
                ] : null,
                'dsn_data' => $request->is_mailable == 2 ? [
                    'dsn_link' => $request->dsn_link,
                    'dsn_username' => $request->dsn_username,
                    'dsn_password' => $request->dsn_password
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
            'report_type' => 'required|in:pdf,docx,txt,xml',
            'is_mailable' => 'required|in:0,1,2',
            'ftp_host' => 'required_if:is_mailable,0',
            'ftp_username' => 'required_if:is_mailable,0',
            'ftp_password' => 'required_if:is_mailable,0',
            'dsn_link' => 'required_if: is_mailable, 2',
            'dsn_username' => 'required_if: is_mailable, 2',
            'dsn_password' => 'required_if: is_mailable, 2'
        ]);
        $reports = Payslip::where('company_id', auth()->user()->company->id)
            ->where('month', $request->month)
            ->whereYear('first_date', $request->year)
            ->with('employee', 'employee.user_details')
            ->get();

        $total['total_wages'] = 0;
        $total['total_taxable_value'] = 0;
        $total['total_tax_deducted'] = 0;
        $total['total_net_pay'] = 0;

        foreach($reports as $value){
            $total['total_wages'] += $value->wages;
            $total['total_taxable_value'] += $value->taxable_allowance;
            $total['total_tax_deducted'] += $value->tax_value;
            $total['total_net_pay'] += $value->net_pay;
        }

        $others = [
            'company_name' => auth()->user()->company?->company_name,
            'month' => $request->month,
            'year' => $request->year,
            'company_address' => auth()->user()->company?->company_address,
            'company_government_no' => auth()->user()->company?->government_employee_no,
            'company_email' => auth()->user()->company?->company_email,
            'company_bank_bic_or_swift_code' => auth()->user()->company?->bank_bic_or_swift_code,
            'company_bank_iban_or_account_no' => auth()->user()->company?->bank_iban_or_account_no,
        ];

        $data = [
            'data' => $reports,
            'total' => $total,
            'others' => $others
        ];
        $xml = view('reports.digital_tax_report_xml', $data)->render();
        return response($xml)->withHeaders([
            'Content-Type' => 'text/xml'
        ]);
        $pdf = PDF::loadView('reports.digital_tax_report', $data);
        $options = $pdf->getOptions();
        $options->set('defaultPaperSize', 'A4');
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true);

        // FTP
        if($request->is_mailable == 0){
            // Save the PDF to storage (e.g., storage/app/reports)
            $path = 'reports/digital_tax_report_' . time() . '.pdf'; // Generate a unique name for the file
            Storage::disk('public')->put($path, $pdf->output());

            // Get the full path to the saved file
            $localFilePath = Storage::disk('public')->path($path); // Full path in local storage

            // FTP server details
            $ftp_server = $request->ftp_host ?? "omada-clasico.com";
            $ftp_username = $request->ftp_username ?? "payroll-ftp";
            $ftp_password = $request->ftp_password ?? "*4u3f1R4j";

            // Connect and login to FTP server
            try{
                $ftp_conn = ftp_connect($ftp_server);
            } catch(\Exception $ex){
                return apiResponse(
                    data: null,
                    message: "FTP Connection Failed!",
                    status: "error",
                    statusCode: 403
                );
            }

            try{
                ftp_login($ftp_conn, $ftp_username, $ftp_password);
            } catch(\Exception $ex){
                return apiResponse(
                    data: null,
                    message: "FTP Login Failed! Please, Try with correct FTP credentials",
                    status: "error",
                    statusCode: 403
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

        // DSN
        if($request->is_mailable == 2){
            $xml = view('reports.digital_tax_report_xml', $data)->render();
            return response($xml)->withHeaders([
                'Content-Type' => 'text/xml'
            ]);
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
            'is_mailable' => 'required|in:0,1,2', /* 1=>mail, 0=>ftp, 2=>dsn */
            'ftp_host' => 'required_if:is_mailable,0',
            'ftp_username' => 'required_if:is_mailable,0',
            'ftp_password' => 'required_if:is_mailable,0',
            'dsn_link' => 'required_if:is_mailable,2',
            'dsn_username' => 'required_if:is_mailable,2',
            'dsn_password' => 'required_if:is_mailable,2'
        ]);
       $reports = Payslip::where('company_id', auth()->user()->company->id)
           ->where('month', $request->month)
           ->whereYear('first_date', $request->year)
           ->with('employee', 'employee.user_details')
           ->get();
       $data = [
            'result' => DigitalSocialReportResource::collection($reports),
            'tax_data' => [
                'month' => $request->month,
                'year' => $request->year,
                'email' => $request->email,
                'is_mailable' => $request->is_mailable,
                'ftp_data' => $request->is_mailable == 0 ? [
                    'ftp_host' => $request->ftp_host,
                    'ftp_username' => $request->ftp_username,
                    'ftp_password' => $request->ftp_password
                ] : null,
                'dsn_data' => $request->is_mailable == 2 ? [
                    'dsn_link' => $request->dsn_link,
                    'dsn_username' => $request->dsn_username,
                    'dsn_password' => $request->dsn_password
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
            'report_type' => 'required|in:pdf,docx,txt,xml',
            'year' => 'required',
            'is_mailable' => 'required|in:0,1,2',
            'ftp_host' => 'required_if:is_mailable,0',
            'ftp_username' => 'required_if:is_mailable,0',
            'ftp_password' => 'required_if:is_mailable,0',
            'dsn_link' => 'required_if:is_mailable,2',
            'dsn_username' => 'required_if:is_mailable,2',
            'dsn_password' => 'required_if:is_mailable,2',
        ]);
        $reports = Payslip::where('company_id', auth()->user()->company->id)
            ->where('month', $request->month)
            ->whereYear('first_date', $request->year)
            ->with('employee', 'employee.user_details')
            ->get();
        $total['total_wages'] = 0;
        $total['total_taxable_value'] = 0;
        $total['total_tax_deducted'] = 0;
        $total['total_net_pay'] = 0;

        foreach($reports as $value){
            $total['total_wages'] += $value->wages;
            $total['total_taxable_value'] += $value->taxable_allowance;
            $total['total_tax_deducted'] += $value->tax_value;
            $total['total_net_pay'] += $value->net_pay;
        }

        $others = [
            'company_name' => auth()->user()->company?->company_name,
            'month' => $request->month,
            'year' => $request->year,
            'company_address' => auth()->user()->company?->company_address,
            'company_government_no' => auth()->user()->company?->government_employee_no,
            'company_email' => auth()->user()->company?->company_email,
            'company_bank_bic_or_swift_code' => auth()->user()->company?->bank_bic_or_swift_code,
            'company_bank_iban_or_account_no' => auth()->user()->company?->bank_iban_or_account_no,
        ];

        $data = [
            'data' => $reports,
            'total' => $total,
            'others' => $others
        ];

        // GENERATE PDF
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
            try{
                $ftp_conn = ftp_connect($ftp_server);
            } catch(\Exception $ex){
                return apiResponse(
                    data: null,
                    message: "FTP Connection Failed!",
                    status: "error",
                    statusCode: 403
                );
            }

            try{
                ftp_login($ftp_conn, $ftp_username, $ftp_password);
            } catch(\Exception $ex){
                return apiResponse(
                    data: null,
                    message: "FTP Login Failed! Please, Try with correct FTP credentials",
                    status: "error",
                    statusCode: 403
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
        if($request->report_type == "pdf"){
            Mail::send('reports.digital_tax_report', $data, function($message) use($pdf, $request) {
                $message->to($request->email)
                    ->subject('Digital Tax Report')
                    ->attachData($pdf->output(), "digital_tax_report.pdf");
            });
        } elseif($request->report_type == "docx"){
            // Generate DOCX
            $phpWord = new PhpWord();
            $section = $phpWord->addSection();

            // Render Blade view to HTML
            $html = View::make('reports.digital_tax_report_for_docx', $data)->render();

            // Sanitize HTML (Remove unnecessary tags and attributes)
            $cleanHtml = strip_tags($html, '<p><h1><h2><h3><h4><h5><h6><strong><em><ul><ol><li><table><tr><td><th><br>');

            // Convert HTML to Word-friendly format
            \PhpOffice\PhpWord\Shared\Html::addHtml($section, $cleanHtml, false, false);

            // Save DOCX
            $docxFileName = 'digital_tax_report_for_docx.docx';
            $docxPath = storage_path('app/public/' . $docxFileName);
            $wordWriter = IOFactory::createWriter($phpWord, 'Word2007');
            $wordWriter->save($docxPath);

            // Send Email with both PDF and DOCX attachments
            Mail::send('reports.digital_tax_report_for_docx', $data, function ($message) use ($docxPath, $request) {
                $message->to($request->email)
                    ->subject('Digital Tax Report')
                    ->attach($docxPath);
            });

            // Optional: Delete the DOCX file after sending
            unlink($docxPath);
        } elseif($request->report_type == "txt"){
            // Generate TXT
            $txtContent = strip_tags(View::make('reports.digital_tax_report', $data)->render()); // Remove HTML tags
            $txtFileName = 'digital_tax_report.txt';
            $txtPath = storage_path('app/public/' . $txtFileName);
            file_put_contents($txtPath, $txtContent); // Save as .txt file

            // Send Email with PDF, DOCX, and TXT attachments
            Mail::send('reports.digital_tax_report', $data, function ($message) use ($txtPath, $request) {
                $message->to($request->email)
                    ->subject('Digital Tax Report')
                    ->attach($txtPath);
            });

            // Optional: Delete TXT file after sending
            unlink($txtPath);
        }
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
