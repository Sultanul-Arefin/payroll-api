<?php

namespace Modules\IRS\Http\Controllers;

use App\Helpers\TaxBanditsHelper;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Modules\Company\Entities\Company;
use Modules\User\Entities\UserDetails;
use Modules\EmployeeSalaryItems\Entities\EmployeeSalaryItem;
use Modules\Payslip\Http\Services\PayslipService;
use App\Models\User;
use Carbon\Carbon;
use Modules\Payslip\Entities\Payslip;
use Illuminate\Database\Eloquent\Builder;
use App\Helpers\XmlValidator;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Modules\IRS\Entities\IrsTransmission;
use Modules\IRS\Entities\TaxbanditsPdfWebhook;
use Modules\LeaveManagement\Entities\UserLeaveDetail;
use setasign\Fpdi\Fpdi;
use Illuminate\Support\Facades\DB;
use Modules\IRS\Entities\Filing;
use Modules\IRS\Entities\IrsFilingLog;
use Modules\IRS\Entities\IrsForm941Record;
use Modules\IRS\Http\Traits\UserInformation;

class IrsController extends Controller
{
    use UserInformation;

    protected $payslipService;

    public function __construct(PayslipService $payslipService)
    {
        $this->payslipService = $payslipService;
    }

    public function generateForm941XML(Request $request)
    {
        $company = $this->getCompany($request->company_id);
        $employeeDetails = $this->getEmployeeDetails($request->employee_id);
        $taxSummary = $this->getTaxSummary($request->employee_id, $company->id, $request->from_date, $request->to_date);
        $employerContributions = $this->getEmployerContributionItems($request->employee_id, $request->from_date, $request->to_date);

        $xml = new \SimpleXMLElement('<Form941></Form941>');

        $xml->addChild('EIN', $company->employer_identification_number);
        $xml->addChild('Name', $company->company_name);
        $xml->addChild('Address', $company->company_address);
        $xml->addChild('Address', $company->company_phone);
        $xml->addChild('Quarter', $this->determineQuarter($request->from_date));
        $xml->addChild('TotalWagesPaid', $taxSummary['taxable_gross_pay'] ?? 0);
        $xml->addChild('FederalIncomeTaxWithheld', $taxSummary['gross_income_tax_category_5']);
        $xml->addChild('SocialSecurityTax', $taxSummary['social_security_tax'] ?? 0);
        $xml->addChild('MedicareTax', $taxSummary['medicare_tax'] ?? 0);
        $xml->addChild('AdditionalWithholding', $taxSummary['additional_tax_category_6']);
        $xml->addChild('TotalTaxLiability', $taxSummary['total_tax']);
        $xml->addChild('ReportingAgentPIN', $company->irs_efile_pin);

        $employerContributionsNode = $xml->addChild('EmployerContributions');
        foreach ($employerContributions as $contribution) {
            $itemNode = $employerContributionsNode->addChild('Contribution');
            $itemNode->addChild('Title', htmlspecialchars($contribution['title']));
            $itemNode->addChild('CompanyAmount', $contribution['company_amount']);
        }

        $fileName = 'irs/form941_' . Str::uuid() . '.xml';
        Storage::disk('local')->put($fileName, $xml->asXML());

        return response()->json([
            'status' => 'success',
            'message' => 'Form 941 XML generated successfully',
            'file_path' => $fileName,
            'xml_content' => $xml->asXML(),
        ]);
    }



    private function getEmployeeCount($fromDate, $toDate)
    {
        // Implement based on your employee model
        return UserDetails::whereBetween('hire_date', [$fromDate, $toDate])
            ->orWhere(function ($q) use ($toDate) {
                $q->where('hire_date', '<=', $toDate)->whereNull('termination_date');
            })
            ->count();
    }

    public function validateXml(Request $request)
    {
        $fileName = $request->get('file_name'); // যেমন: irs/form941_0a2fa530-4345-448a-840f-02e46e6a8b50.xml

        if (!$fileName) {
            return response()->json(
                [
                    'status' => 'error',
                    'message' => 'No file_name parameter provided.',
                ],
                400,
            );
        }

        if (!Storage::exists($fileName)) {
            return response()->json(
                [
                    'status' => 'error',
                    'message' => "XML file '{$fileName}' not found in storage.",
                ],
                404,
            );
        }

        $xmlContent = Storage::get($fileName);

        $xsdPath = storage_path('app/xsd/IRS941.xsd');

        if (!file_exists($xsdPath)) {
            return response()->json(
                [
                    'status' => 'error',
                    'message' => 'XSD schema file not found at path: ' . $xsdPath,
                ],
                500,
            );
        }

        $result = \App\Helpers\XmlValidator::validate($xmlContent, $xsdPath);

        if ($result['status']) {
            return response()->json([
                'status' => 'success',
                'message' => 'XML is valid according to IRS XSD schema.',
            ]);
        } else {
            return response()->json(
                [
                    'status' => 'fail',
                    'message' => 'XML failed validation',
                    'errors' => $result['errors'],
                ],
                422,
            );
        }
    }

    private function getCompany($companyId)
    {
        return Company::findOrFail($companyId);
    }

    private function getEmployeeDetails($employeeId)
    {
        return UserDetails::with('user')->where('user_id', $employeeId)->firstOrFail();
    }

    private function getTaxSummary($employeeId, $companyId, $fromDate, $toDate)
    {
        $payroll = $this->payslipService;

        $payslips = Payslip::where('employee_id', $employeeId)
            ->where('company_id', $companyId)
            ->whereBetween('first_date', [$fromDate, $toDate])
            ->get();

        $taxable_gross_pay = 0;
        foreach ($payslips as $payslip) {
            $wages = $payslip->wages ?? 0;
            $additional_pay = $payslip->additional_pay ?? 0;
            $leave_deduction = $payslip->leave_deduction ?? 0;
            $taxable_allowance = $payslip->taxable_allowance ?? 0;
            $non_taxable_allowance = $payslip->non_taxable_allowance ?? 0;

            $taxableGross = $wages + $additional_pay - $leave_deduction + $taxable_allowance + $non_taxable_allowance - $non_taxable_allowance;
            $taxable_gross_pay += $taxableGross;
        }

        $categoryItems = EmployeeSalaryItem::with('salaryItemsName')
            ->whereHas('salaryItemsName', function ($query) {
                $query->whereIn('salary_items_category_id', [6]);
            })
            ->where('company_id', $companyId)
            ->where(function ($query) use ($employeeId) {
                $query->where('employee_id', $employeeId);
            })
            ->get();

        $category5Items = [];
        $category6Items = [];
        $socialSecurityTax = 0;
        $medicareTax = 0;

        foreach ($categoryItems as $item) {
            $itemName = strtolower(str_replace(' ', '_', $item->salaryItemsName->name ?? 'unknown'));
            $categoryId = $item->salaryItemsName->salary_items_category_id ?? 0;

            $thresholdAmount = $item->threshold_amount ?? null;
            $thresholdType = $item->threshold_type ?? null;
            $isPercentage = $item->is_percentage ?? 0;
            $amountValue = $item->amount ?? 0;

            $apply = true;
            if ($thresholdAmount !== null) {
                if ($thresholdType === 'greater' && $taxable_gross_pay <= $thresholdAmount) {
                    $apply = false;
                } elseif ($thresholdType === 'less' && $taxable_gross_pay >= $thresholdAmount) {
                    $apply = false;
                }
            }
            if (!$apply) {
                continue;
            }

            $calculatedAmount = $isPercentage ? round($taxable_gross_pay * ($amountValue / 100), 2) : round($amountValue, 2);

            if (str_contains($itemName, 'social_security')) {
                $socialSecurityTax += $calculatedAmount;
                continue;
            }

            if (str_contains($itemName, 'medicare')) {
                $medicareTax += $calculatedAmount;
                continue;
            }

            $entry = ['name' => $itemName, 'amount' => $calculatedAmount];

            if ($categoryId == 5) {
                $category5Items[] = $entry;
            } elseif ($categoryId == 6) {
                $category6Items[] = $entry;
            }
        }

        $incomeTaxSum = $payroll->get_income_taxes_amount($employeeId, $companyId, $fromDate, $toDate);
        $additionalTaxSum = $payroll->get_additional_taxes_tax_top_up_amount($employeeId, $companyId, $fromDate, $toDate);

        $totalTax = $incomeTaxSum + $additionalTaxSum + $socialSecurityTax + $medicareTax + array_sum(array_column($category5Items, 'amount')) + array_sum(array_column($category6Items, 'amount'));

        return [
            'taxable_gross_pay' => round($taxable_gross_pay, 2),
            'gross_income_tax_category_5' => round($incomeTaxSum, 2),
            'additional_tax_category_6' => round($additionalTaxSum, 2),
            'social_security_tax' => round($socialSecurityTax, 2),
            'medicare_tax' => round($medicareTax, 2),
            'category_5_items' => $category5Items,
            'category_6_items' => $category6Items,
            'total_tax' => round($totalTax, 2),
        ];
    }

    private function getSocialDeduction($payslip)
    {
        $deduction_details = \Modules\Payslip\Entities\PayslipDetailsForDeduction::query()
            ->whereHas('salary_item_name', function ($q) {
                $q->where('salary_items_category_id', 7);
            })
            ->where('payslip_id', $payslip->id)
            ->get();

        $response = [];
        foreach ($deduction_details as $value) {
            $response[] = [
                'title' => $value?->salary_item_name?->name,
                'base' => $payslip->gross_pay_before_tax ?? 0,
                'employee_rate' => $value->employee_amount_rate,
                'employee_amount' => $value->employee_amount,
                'company_rate' => $value->government_or_company_amount_rate,
                'company_amount' => $value->government_or_company_amount,
            ];
        }

        return $response;
    }

    private function getEmployerContributionItems($employeeId, $fromDate, $toDate)
    {
        $payslips = \Modules\Payslip\Entities\Payslip::where('employee_id', $employeeId)
            ->whereBetween('first_date', [$fromDate, $toDate])
            ->get();

        $contributions = [];

        foreach ($payslips as $payslip) {
            $items = $this->getSocialDeduction($payslip);
            foreach ($items as $item) {
                $key = strtolower(str_replace(' ', '_', $item['title']));
                if (!isset($contributions[$key])) {
                    $contributions[$key] = [
                        'title' => $item['title'],
                        'company_amount' => 0,
                    ];
                }
                $contributions[$key]['company_amount'] += $item['company_amount'];
            }
        }

        return array_values(
            array_map(function ($item) {
                return [
                    'title' => $item['title'],
                    'company_amount' => round($item['company_amount'], 2),
                ];
            }, $contributions),
        );
    }

    public function generatePdfFromXml(Request $request)
    {
        try {
            // Validate input
            $request->validate([
                'file_name' => 'required|string',
            ]);

            $xmlFileName = $request->input('file_name');
            $xmlFilePath = storage_path('app/irs/' . basename($xmlFileName));

            if (!file_exists($xmlFilePath)) {
                return response()->json(['error' => 'XML file not found'], 404);
            }

            // Parse XML data
            $xml = simplexml_load_file($xmlFilePath);
            $data = [
                'ein' => (string) $xml->EIN,
                'businessName' => (string) $xml->BusinessName,
                'tradeName' => (string) $xml->TradeName,
                'address' => (string) $xml->Address,
                'city' => (string) $xml->City,
                'state' => (string) $xml->State,
                'zip' => (string) $xml->ZIP,
                'quarter' => (string) $xml->Quarter,
                'year' => (string) $xml->TaxYear,
                'employeeCount' => (int) $xml->TotalEmployees,
                'totalWages' => (float) $xml->TotalWagesPaid,
                'federalTaxWithheld' => (float) $xml->FederalIncomeTaxWithheld,
                'ssWages' => (float) $xml->SocialSecurityTax->Wages,
                'ssTax' => (float) $xml->SocialSecurityTax->Tax,
                'medicareWages' => (float) $xml->MedicareTax->Wages,
                'medicareTax' => (float) $xml->MedicareTax->Tax,
                'totalTaxLiability' => (float) $xml->TotalTaxLiability,
                'depositSchedule' => (string) $xml->DepositSchedule->Type,
                'closedBusiness' => isset($xml->BusinessClosed) ? true : false,
            ];

            // Initialize PDF
            $pdf = new Fpdi();
            $pdf->AddPage();
            $pdf->setSourceFile(storage_path('app/templates/f941.pdf'));
            $tplIdx = $pdf->importPage(1);
            $pdf->useTemplate($tplIdx);

            // Set font
            $pdf->SetFont('Helvetica', '', 10);
            $pdf->SetTextColor(0, 0, 0);

            // ===== PAGE 1 FIELDS =====

            // Header Section
            $pdf->SetXY(140, 30); // EIN
            $pdf->Write(0, $data['ein']);

            $pdf->SetXY(25, 30); // Business Name
            $pdf->Write(0, substr($data['businessName'], 0, 70));

            $pdf->SetXY(25, 37); // Trade Name
            $pdf->Write(0, substr($data['tradeName'], 0, 70));

            $pdf->SetXY(25, 44); // Address
            $pdf->Write(0, substr($data['address'], 0, 70));

            $pdf->SetXY(25, 51); // City, State ZIP
            $pdf->Write(0, "{$data['city']}, {$data['state']} {$data['zip']}");

            // Quarter Selection
            $quarterY = 67;
            for ($i = 1; $i <= 4; $i++) {
                if ($data['quarter'] == $i) {
                    $pdf->SetXY(15 + ($i - 1) * 45, $quarterY);
                    $pdf->Write(0, 'X');
                }
            }

            // Part 1 - Employee and Wage Information
            $pdf->SetXY(155, 82); // Line 1 - Employee Count
            $pdf->Write(0, $data['employeeCount']);

            $pdf->SetXY(155, 89); // Line 2 - Total Wages
            $pdf->Write(0, number_format($data['totalWages'], 2));

            $pdf->SetXY(155, 96); // Line 3 - Federal Tax Withheld
            $pdf->Write(0, number_format($data['federalTaxWithheld'], 2));

            // Social Security and Medicare Taxes
            $pdf->SetXY(155, 103); // Line 5a - SS Wages
            $pdf->Write(0, number_format($data['ssWages'], 2));

            $pdf->SetXY(155, 110); // Line 5a - SS Tax
            $pdf->Write(0, number_format($data['ssTax'], 2));

            $pdf->SetXY(155, 117); // Line 5c - Medicare Wages
            $pdf->Write(0, number_format($data['medicareWages'], 2));

            $pdf->SetXY(155, 124); // Line 5c - Medicare Tax
            $pdf->Write(0, number_format($data['medicareTax'], 2));

            // Total Taxes
            $pdf->SetXY(155, 152); // Line 10 - Total Tax Liability
            $pdf->Write(0, number_format($data['totalTaxLiability'], 2));

            // ===== PAGE 2 FIELDS =====
            $pdf->AddPage();
            $tplIdx2 = $pdf->importPage(2);
            $pdf->useTemplate($tplIdx2);

            // Part 2 - Deposit Schedule
            if ($data['depositSchedule'] === 'Monthly') {
                $pdf->SetXY(15, 45);
                $pdf->Write(0, 'X');
            } else {
                $pdf->SetXY(15, 52);
                $pdf->Write(0, 'X');
            }

            // Part 3 - Business Status
            if ($data['closedBusiness']) {
                $pdf->SetXY(15, 85);
                $pdf->Write(0, 'X');
            }

            // Save PDF
            $outputFileName = 'form941_' . Str::uuid() . '.pdf';
            $outputPath = storage_path("app/irs/{$outputFileName}");
            $pdf->Output($outputPath, 'F');

            return response()->json([
                'status' => 'success',
                'message' => 'Form 941 PDF generated successfully',
                'download_url' => url("storage/irs/{$outputFileName}"),
                'file_path' => $outputFileName,
            ]);
        } catch (\Exception $e) {
            return response()->json(
                [
                    'status' => 'error',
                    'message' => 'Failed to generate PDF',
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ],
                500,
            );
        }
    }

    //   public function submitForm941ToTaxBandits(Request $request)
    // {
    //     $fileName = $request->get('file_name');
    //     $company = $this->getCompany($request->company_id); // dynamically get TIN & name

    //     if (!$fileName || !Storage::exists($fileName)) {
    //         return response()->json([
    //             'status' => 'error',
    //             'message' => 'XML file not found or invalid filename.'
    //         ], 404);
    //     }

    //     $xmlContent = Storage::get($fileName);

    //     $endpoint = env('TAXBANDITS_BASE_URL') . '/Form941/RequestSubmission';
    //     $apiKey = env('TAXBANDITS_API_KEY');
    //     $secretKey = env('TAXBANDITS_SECRET_KEY');

    //     $response = Http::post($endpoint, [
    //         'UserCredentials' => [
    //             'APIKey' => $apiKey,
    //             'SecretKey' => $secretKey
    //         ],
    //         'TaxYear' => date('Y'),
    //         'TIN' => $company->employer_identification_number,
    //         'IsFederalFiling' => true,
    //         'IsStateFiling' => false,
    //         'IsPostal' => false,
    //         'IsOnlineAccess' => false,
    //         'ReturnHeader' => [
    //             'Business' => [
    //                 'TIN' => $company->employer_identification_number,
    //                 'BusinessName' => $company->company_name,
    //                 'IsEIN' => true
    //             ]
    //         ],
    //         'Form941Records' => [
    //             [
    //                 'RecordId' => Str::uuid(),
    //                 'XmlData' => base64_encode($xmlContent),
    //             ]
    //         ]
    //     ]);

    //     return response()->json([
    //         'status' => 'success',
    //         'message' => 'Form 941 submitted to TaxBandits successfully',
    //         'http_status' => $response->status(),
    //         'raw_body' => $response->body(), // <-- Add this for debugging
    //         'response' => $response->json()
    //     ]);
    // }

    // public function submitForm941ToTaxBandits(Request $request)
    // {
    //     $fileName = $request->get('file_name');
    //     $companyId = $request->get('company_id');

    //     if (!$fileName || !Storage::exists($fileName)) {
    //         return response()->json([
    //             'status' => 'error',
    //             'message' => 'XML file not found or invalid filename.'
    //         ], 404);
    //     }

    //     $company = $this->getCompany($companyId);
    //     if (!$company) {
    //         return response()->json([
    //             'status' => 'error',
    //             'message' => 'Company not found with the given ID.'
    //         ], 404);
    //     }

    //     $xmlContent = Storage::get($fileName);

    //     // Config values
    //     $authUrl = rtrim(config('services.taxbandits.auth_url'), '/');
    //     $apiUrl  = rtrim(config('services.taxbandits.api_url'), '/');
    //     $clientId = config('services.taxbandits.client_id');
    //     $clientSecret = config('services.taxbandits.client_secret');
    //     $userToken = config('services.taxbandits.user_token');

    //     // Step 1: Get Access Token
    //     $authResponse = Http::asForm()->post($authUrl . '/AccessToken', [
    //         'ClientID' => $clientId,
    //         'ClientSecret' => $clientSecret,
    //         'UserToken' => $userToken,
    //     ]);

    //     $authData = $authResponse->json();

    //     if (!$authResponse->ok() || empty($authData['AccessToken'])) {
    //         return response()->json([
    //             'status' => 'error',
    //             'message' => 'Failed to authenticate with TaxBandits API.',
    //             'auth_error' => $authData,
    //         ], 401);
    //     }

    //     $accessToken = $authData['AccessToken'];

    //     // Step 2: Build payload
    //     $payload = [
    //         'TaxYear' => date('Y'),
    //         'TIN' => $company->employer_identification_number,
    //         'IsFederalFiling' => true,
    //         'IsStateFiling' => false,
    //         'IsPostal' => false,
    //         'IsOnlineAccess' => false,
    //         'ReturnHeader' => [
    //             'Business' => [
    //                 'TIN' => $company->employer_identification_number,
    //                 'BusinessName' => $company->company_name,
    //                 'IsEIN' => true,
    //             ],
    //         ],
    //         'Form941Records' => [
    //             [
    //                 'RecordId' => (string) Str::uuid(),
    //                 'XmlData' => base64_encode($xmlContent),
    //             ],
    //         ],
    //     ];

    //     // Step 3: Submit Form 941 using AccessToken
    //     $endpoint = $apiUrl . '/Form941/RequestSubmission';

    //     try {
    //         $response = Http::withHeaders([
    //             'Authorization' => 'Bearer ' . $accessToken,
    //             'Content-Type' => 'application/json',
    //         ])->post($endpoint, $payload);

    //         if ($response->failed()) {
    //             return response()->json([
    //                 'status' => 'error',
    //                 'message' => 'Failed to submit Form 941 to TaxBandits.',
    //                 'http_status' => $response->status(),
    //                 'raw_body' => $response->body(),
    //             ], $response->status());
    //         }

    //         return response()->json([
    //             'status' => 'success',
    //             'message' => 'Form 941 submitted to TaxBandits successfully.',
    //             'http_status' => $response->status(),
    //             'response' => $response->json(),
    //         ]);

    //     } catch (\Exception $e) {
    //         return response()->json([
    //             'status' => 'error',
    //             'message' => 'Exception occurred: ' . $e->getMessage(),
    //         ], 500);
    //     }
    // }

    private function getQuarterDateRange($year, $quarter)
    {
        switch ($quarter) {
            case 'Q1':
                return [
                    Carbon::create($year, 1, 1)->startOfDay(),
                    Carbon::create($year, 3, 31)->endOfDay(),
                ];
            case 'Q2':
                return [
                    Carbon::create($year, 4, 1)->startOfDay(),
                    Carbon::create($year, 6, 30)->endOfDay(),
                ];
            case 'Q3':
                return [
                    Carbon::create($year, 7, 1)->startOfDay(),
                    Carbon::create($year, 9, 30)->endOfDay(),
                ];
            case 'Q4':
                return [
                    Carbon::create($year, 10, 1)->startOfDay(),
                    Carbon::create($year, 12, 31)->endOfDay(),
                ];
            default:
                throw new \InvalidArgumentException("Invalid quarter: $quarter");
        }
    }

    public function submitForm941JsonToTaxBandits(Request $request)
    {
        $data = $request->all(); // JSON body

        //$companyId = $data['company_id'] ?? null;
        $companyId = auth()->user()->company_id;

        $year = $data['Form941Records'][0]['ReturnHeader']['TaxYr'] ?? now()->year;
        $quarter = $data['Form941Records'][0]['ReturnHeader']['Qtr'] ?? 'Q2';

        $company = $this->getCompany($companyId);
        if (!$company) {
            return response()->json(
                [
                    'status' => 'error',
                    'message' => 'Company not found with the given ID.',
                ],
                404,
            );
        }

        // Quarter date range 
        [$startDate, $endDate] = $this->getQuarterDateRange($year, $quarter);


        $salaryResource = new \Modules\IRS\Http\Resources\GetSalaryResource($company, $startDate, $endDate);
        $salaryData = $salaryResource->toArray(request());

        $grossPay         = $salaryData['gross_pay'] ?? 0;
        

        // Additional taxes
        $additionalTaxes = $salaryData['additional_taxes'] ?? [];
        $federalIncomeTaxWithheld = floatval($additionalTaxes['Federal Income Tax'] ?? 0);
        $socialSecurityTaxAmt = floatval($additionalTaxes['Social Security'] ?? 0);
        $medicareWagesTipsAmt = floatval($additionalTaxes['Medicare'] ?? 0);
        // Government deductions
        $governmentDeductions = $salaryData['government_deductions_yearly'] ?? [];
        $socialSecurityTaxCon = floatval($governmentDeductions['Social Security'] ?? 0);
        $medicareWagesTipsCom = floatval($governmentDeductions['Medicare'] ?? 0);


        $totalEmployees  = $salaryData['total_employee'] ?? 2;
        $companyName     = $salaryData['company_name'] ?? $company->name;
        $ein             = $salaryData['employer_identification_number'] ?? $company->employer_identification_number;
        $companyEmail     = $salaryData['company_email'] ?? $company->company_email;
        $companyPhone     = $salaryData['company_phone'] ?? $company->company_phone;
    

        // Load credentials
        $clientId = config('services.taxbandits.client_id');
        $clientSecret = config('services.taxbandits.client_secret');
        $userToken = config('services.taxbandits.user_token');
        $authUrl = config('services.taxbandits.auth_url');
        $apiUrl = config('services.taxbandits.api_url');

        if (empty($userToken) || empty($clientId) || empty($clientSecret)) {
            return response()->json(
                [
                    'status' => 'error',
                    'message' => 'Missing TaxBandits credentials.',
                ],
                400,
            );
        }

        // Step 1: Generate JWT token
        try {
            $jwtToken = $this->generateTaxBanditsJWT($clientId, $clientSecret, $userToken);
        } catch (\Exception $e) {
            return response()->json(
                [
                    'status' => 'error',
                    'message' => 'Failed to generate JWT token: ' . $e->getMessage(),
                ],
                500,
            );
        }

        // Step 2: Get Access Token
        $authResponse = Http::withHeaders([
            'Authentication' => $jwtToken,
        ])->get($authUrl);

        if ($authResponse->failed()) {
            return response()->json(
                [
                    'status' => 'error',
                    'message' => 'Authentication failed.',
                    'details' => $authResponse->json(),
                ],
                401,
            );
        }

        $accessToken = $authResponse['AccessToken'] ?? null;
        if (!$accessToken) {
            return response()->json(
                [
                    'status' => 'error',
                    'message' => 'Access token not received.',
                    'auth_response' => $authResponse->json(),
                ],
                401,
            );
        }

        // Step 3: Prepare tax values with safety checks
        $form941Record = $data['Form941Records'][0] ?? [];

        $returnData = $form941Record['ReturnData']['Form941'] ?? [];

        $federalIncomeTaxWithheld = $federalIncomeTaxWithheld ?? 0;
        // $socialSecurityTax = ($socialSecurityTaxAmt + $socialSecurityTaxCon) ?? 0;
        // $medicareTax = ($medicareWagesTipsAmt + $medicareWagesTipsCom) ?? 0;
        $socialSecurityRate = 0.124; // 12.4%
        $medicareRate       = 0.029; 

        $socialSecurityTax =round($socialSecurityTaxCon) +  round($socialSecurityTaxAmt);
        $medicareTax       = round($medicareWagesTipsCom) + round($medicareWagesTipsAmt);
        
        $totalTaxBeforeAdjustmentAmt = floatval($returnData['TotalTaxBeforeAdjustmentAmt'] ?? $federalIncomeTaxWithheld + $socialSecurityTax + $medicareTax);

        $payrollTaxCreditAmt = floatval($returnData['PayrollTaxCreditAmt'] ?? 0);

        // TotalTaxAfterAdjustmentAmt must equal sum of Lines 6,7,8,9; if not provided, calculate
        $totalTaxAfterAdjustmentAmt = floatval($returnData['TotalTaxAfterAdjustmentAmt'] ?? $totalTaxBeforeAdjustmentAmt);

        $totTaxAfterAdjustmentAndNonRfdCr = floatval($returnData['TotTaxAfterAdjustmentAndNonRfdCr'] ?? $totalTaxAfterAdjustmentAmt);

        $totTaxDepositAmt = floatval($returnData['TotTaxDepositAmt'] ?? 0);

        $balanceDueAmt = $totTaxAfterAdjustmentAndNonRfdCr - $totTaxDepositAmt;

        // Deposit Schedule setup
        // Deposit Schedule setup
        $depositSchedule = $form941Record['ReturnData']['DepositScheduleType'] ?? [];

        
        $depositorType = $data['Form941Records'][0]['ReturnData']['DepositScheduleType']['DepositorType']
            ?? $depositSchedule['DepositorType']
            ?? 'NONE';


        // $totalQuarterTaxLiabilityAmt = round($federalIncomeTaxWithheld + $socialSecurityTax + $medicareTax, 2);

        // if (empty($depositorType) || strtoupper($depositorType) === 'NONE') {
        //     if ($totalQuarterTaxLiabilityAmt < 2500) {
        //         $depositorType = 'NONE';
        //         $monthlyDepositor = null;
        //     } else {
        //         $depositorType = 'MONTHLY';
        //         $perMonth = round($totalQuarterTaxLiabilityAmt / 3, 2);
        //         $monthlyDepositor = [
        //             'TaxLiabilityMonth1' => $perMonth,
        //             'TaxLiabilityMonth2' => $perMonth,
        //             'TaxLiabilityMonth3' => round($totalQuarterTaxLiabilityAmt - 2 * $perMonth, 2),
        //         ];
        //     }
        // } else {
            
        //     if (strtoupper($depositorType) === 'MONTHLY') {
        //         $perMonth = round($totalQuarterTaxLiabilityAmt / 3, 2);
        //     $monthlyDepositor = [
        //             'TaxLiabilityMonth1' => $perMonth,
        //             'TaxLiabilityMonth2' => $perMonth,
        //             'TaxLiabilityMonth3' => round($totalQuarterTaxLiabilityAmt - 2 * $perMonth, 2),
        //         ];
        //     } else {
        //         $monthlyDepositor = null;
        //     }

            
        // }

        $totalQuarterTaxLiabilityAmt = round($federalIncomeTaxWithheld + $socialSecurityTax + $medicareTax, 2);

        // Determine depositor type
        $depositorTypeInput = strtoupper($depositorType ?? 'NONE');
        $monthlyDepositor = null;
        $semiWeeklyDepositor = null;

        if ($totalQuarterTaxLiabilityAmt < 2500) {
            // Small tax liability → no deposit required
            $depositorType = 'NONE';
        } else {
            // Tax liability >= 2500 → need deposit
            if ($depositorTypeInput === 'MONTHLY') {
                $perMonth = round($totalQuarterTaxLiabilityAmt / 3, 2);
                $monthlyDepositor = [
                    'TaxLiabilityMonth1' => $perMonth,
                    'TaxLiabilityMonth2' => $perMonth,
                    'TaxLiabilityMonth3' => round($totalQuarterTaxLiabilityAmt - 2 * $perMonth, 2),
                ];
            } elseif ($depositorTypeInput === 'SEMIWEEKLY') {
                // Semi-weekly depositor calculation
                // IRS requires 1-2 weeks of payroll tax deposits
                // Approx: 13 weeks per quarter → 6,6,1 weeks
                $weeklyLiability = round($totalQuarterTaxLiabilityAmt / 13, 2);

                $semiWeeklyDepositor = [];
                for ($i = 1; $i <= 13; $i++) {
                    $semiWeeklyDepositor['Week'.$i.'TaxLiability'] = $weeklyLiability;
                }

                // Adjust last week to match exact total
                $sumWeeks = array_sum($semiWeeklyDepositor);
                $difference = $totalQuarterTaxLiabilityAmt - $sumWeeks;
                $semiWeeklyDepositor['Week13TaxLiability'] += $difference;

                $depositorType = 'SEMIWEEKLY';
            } else {
                // Default to monthly if unknown
                $depositorType = 'MONTHLY';
                $perMonth = round($totalQuarterTaxLiabilityAmt / 3, 2);
                $monthlyDepositor = [
                    'TaxLiabilityMonth1' => $perMonth,
                    'TaxLiabilityMonth2' => $perMonth,
                    'TaxLiabilityMonth3' => round($totalQuarterTaxLiabilityAmt - 2 * $perMonth, 2),
                ];
            }
            }
        $payerRef = "IRS-" ."941-" . $companyId . "-" . now()->year . $quarter . "-" . uniqid();

        // Build JSON payload for TaxBandits API
        $requestPayload = [
            'Form941Records' => [
                [
                    //'SequenceId' => $form941Record['SequenceId'] ?? uniqid(),
                    'SequenceId' => substr(md5(uniqid(mt_rand(), true)), 0, 10),
                    'ReturnHeader' => [
                        'ReturnType' => $form941Record['ReturnHeader']['ReturnType'] ?? 'FORM941',
                        'TaxYr' => $form941Record['ReturnHeader']['TaxYr'] ?? '2025',
                        'Qtr' => $form941Record['ReturnHeader']['Qtr'] ?? 'Q1',
                        'Business' => [
                            'BusinessId' => $form941Record['ReturnHeader']['Business']['BusinessId'] ?? null,
                            'BusinessNm' => $companyName ?? null,
                            'TradeNm' => $form941Record['ReturnHeader']['Business']['TradeNm'] ?? null,
                            'PayerRef' => $payerRef,
                            'IsEIN' => true,
                            'EINorSSN' => $ein ?? '127496780',
                            'Email' => $companyEmail ?? 'test@example.com',
                            'ContactNm' => $company->contact_name ?? 'John Doe',
                            //'Phone' => $companyPhone  ?? '1234567890',
                            'Phone' => $form941Record['ReturnHeader']['Business']['Phone'] ?? '',
                            'PhoneExtn' => $form941Record['ReturnHeader']['Business']['PhoneExtn'] ?? '',
                            'Fax' => $form941Record['ReturnHeader']['Business']['Fax'] ?? null,
                            'BusinessType' => $form941Record['ReturnHeader']['Business']['BusinessType'] ?? 'CORP',
                            'SigningAuthority' => [
                                'Name' => $company->contact_name ?? 'John Doe',
                                'Phone' => preg_replace('/[^0-9]/', '', $company->phone ?? '1234567890'),
                                'BusinessMemberType' => 'TAXOFFICER',
                            ],
                            'KindOfEmployer' => $form941Record['ReturnHeader']['Business']['KindOfEmployer'] ?? null,
                            'KindOfPayer' => $form941Record['ReturnHeader']['Business']['KindOfPayer'] ?? null,
                            'IsBusinessTerminated' => $form941Record['ReturnHeader']['Business']['IsBusinessTerminated'] ?? false,
                            'IsForeign' => $form941Record['ReturnHeader']['Business']['IsForeign'] ?? false,
                            'USAddress' => $form941Record['ReturnHeader']['Business']['USAddress'] ?? null,
                            'ForeignAddress' => $form941Record['ReturnHeader']['Business']['ForeignAddress'] ?? null,
                        ],
                        'IsThirdPartyDesignee' => $form941Record['ReturnHeader']['IsThirdPartyDesignee'] ?? false,
                        'ThirdPartyDesignee' => $form941Record['ReturnHeader']['ThirdPartyDesignee'] ?? [
                            'Name' => null,
                            'Phone' => null,
                            'PIN' => null,
                        ],
                        'SignatureDetails' => $form941Record['ReturnHeader']['SignatureDetails'] ?? [
                            'SignatureType' => 'FORM_8453_EMP',
                            'OnlineSignaturePIN' => ['PIN' => null],
                            'ReportingAgentPIN' => ['PIN' => null],
                            'taxPayerPIN' => ['PIN' => null],
                            'Form8453EMP' => null,
                        ],
                        'BusinessStatusDetails' => $form941Record['ReturnHeader']['BusinessStatusDetails'] ?? [
                            'IsBusinessClosed' => false,
                            'BusinessClosedDetails' => null,
                            'IsBusinessTransferred' => false,
                            'BusinessTransferredDetails' => null,
                            'IsSeasonalEmployer' => false,
                        ],
                    ],
                    'ReturnData' => [
                        'Form941' => [
                            'EmployeeCnt' => intval($totalEmployees ?? 0),
                            'WagesAmt' => round(floatval($grossPay ?? 0),2),
                            'FedIncomeTaxWHAmt' => $federalIncomeTaxWithheld,
                            'WagesNotSubjToSSMedcrTaxInd' => $returnData['WagesNotSubjToSSMedcrTaxInd'] ?? false,
                            'SocialSecurityTaxCashWagesAmt_Col1' => round(floatval($grossPay ?? 0), 2),
                            'TaxableSocSecTipsAmt_Col1' => round(floatval($returnData['TaxableSocSecTipsAmt_Col1'] ?? 0),2),
                            'TaxableMedicareWagesTipsAmt_Col1' => round(floatval($grossPay ?? 0),2),
                            'TxblWageTipsSubjAddnlMedcrAmt_Col1' => round(floatval($returnData['TxblWageTipsSubjAddnlMedcrAmt_Col1'] ?? 0),2),
                            'SocialSecurityTaxAmt_Col2' => $socialSecurityTax,
                            'TaxOnSocialSecurityTipsAmt_Col2' => round(floatval($returnData['TaxOnSocialSecurityTipsAmt_Col2'] ?? 0),2),
                            'TaxOnMedicareWagesTipsAmt_Col2' => $medicareTax,
                            'TaxOnWageTipsSubjAddnlMedcrAmt_Col2' => floatval($returnData['TaxOnWageTipsSubjAddnlMedcrAmt_Col2'] ?? 0),
                            'TotSSMdcrTaxAmt' => $socialSecurityTax + $medicareTax,
                            'TaxOnUnreportedTips3121qAmt' => floatval($returnData['TaxOnUnreportedTips3121qAmt'] ?? 0),
                            'TotalTaxBeforeAdjustmentAmt' => $totalTaxBeforeAdjustmentAmt,
                            'CurrentQtrFractionsCentsAmt' => floatval($returnData['CurrentQtrFractionsCentsAmt'] ?? 0),
                            'CurrentQuarterSickPaymentAmt' => floatval($returnData['CurrentQuarterSickPaymentAmt'] ?? 0),
                            'CurrQtrTipGrpTermLifeInsAdjAmt' => floatval($returnData['CurrQtrTipGrpTermLifeInsAdjAmt'] ?? 0),
                            'TotalTaxAfterAdjustmentAmt' => $totalTaxAfterAdjustmentAmt,
                            'PayrollTaxCreditAmt' => $payrollTaxCreditAmt,
                            'IsPayrollTaxCredit' => $returnData['IsPayrollTaxCredit'] ?? false,
                            'Form8974' => $returnData['Form8974'] ?? null,
                            'TotTaxAfterAdjustmentAndNonRfdCr' => $totTaxAfterAdjustmentAndNonRfdCr,
                            'TotTaxDepositAmt' => $totTaxDepositAmt,
                            'BalanceDueAmt' => $balanceDueAmt,
                            'OverpaidAmt' => floatval($returnData['OverpaidAmt'] ?? 0),
                            'OverPaymentRecoveryType' => $returnData['OverPaymentRecoveryType'] ?? null,
                        ],
                        'IRSPaymentType' => $form941Record['ReturnData']['IRSPaymentType'] ?? 'EFTPS',
                        'IRSPayment' => $form941Record['ReturnData']['IRSPayment'] ?? [
                            'BankRoutingNum' => null,
                            'AccountType' => null,
                            'BankAccountNum' => null,
                            'Phone' => null,
                        ],
                        'DepositScheduleType' => [
                            'DepositorType' => $depositorType,
                            'MonthlyDepositor' => $monthlyDepositor,
                            'SemiWeeklyDepositor' => null,
                            'TotalQuarterTaxLiabilityAmt' => $totalQuarterTaxLiabilityAmt,
                        ],
                        
                    ],
                ],
            ],
        ];

        // Step 5: Submit JSON payload to TaxBandits API
        $endpoint = $apiUrl . '/Form941/Create';

        $transmitResponse = Http::withHeaders([
            'Authorization' => 'Bearer ' . $accessToken,
            'Content-Type'  => 'application/json',
            'Accept'        => 'application/json',
        ])->post($endpoint, $requestPayload);

        if ($transmitResponse->successful()) {

            // Parse response
            $responseJson  = $transmitResponse->json();
            $submissionId  = $responseJson['SubmissionId'] ?? null;
            $recordId      = $responseJson['Form941Records']['SuccessRecords'][0]['RecordId'] ?? null;

            // Save filing record
            $filing = Filing::create([
                'form_type'     => 'FORM941',
                'quarter'       => $quarter,
                'created_date'  => now(),
                'status'        => 'Draft', 
                'submission_id' => $submissionId,
                'record_id'     => $recordId,
                'form_data'     => json_encode($requestPayload),
                'api_response'  => json_encode($responseJson), // better to store as JSON string
            ]);

            return response()->json([
                'status'    => 'success',
                'message'   => 'Form 941 JSON submitted successfully.',
                'response'  => $responseJson,
                'filing_id' => $filing->id,
            
            ]);

            } else {
                return response()->json([
                    'status'      => 'error',
                    'message'     => 'Failed to submit JSON to TaxBandits.',
                    'http_status' => $transmitResponse->status(),
                    'raw_body'    => $transmitResponse->body(),
                ],$transmitResponse->status());
            }
    }


    public function listForm941(Request $request)
    {
        // Load credentials
        $clientId = config('services.taxbandits.client_id');
        $clientSecret = config('services.taxbandits.client_secret');
        $userToken = config('services.taxbandits.user_token');
        $authUrl = config('services.taxbandits.auth_url');
        $apiUrl = config('services.taxbandits.api_url');

        if (empty($userToken) || empty($clientId) || empty($clientSecret)) {
            return response()->json(
                [
                    'status' => 'error',
                    'message' => 'Missing TaxBandits credentials.',
                ],
                400,
            );
        }

        // Step 1: Generate JWT token
        try {
            $jwtToken = $this->generateTaxBanditsJWT($clientId, $clientSecret, $userToken);
        } catch (\Exception $e) {
            return response()->json(
                [
                    'status' => 'error',
                    'message' => 'Failed to generate JWT token: ' . $e->getMessage(),
                ],
                500,
            );
        }

        // Step 2: Get Access Token
        $authResponse = Http::withHeaders([
            'Authentication' => $jwtToken,
        ])->get($authUrl);

        if ($authResponse->failed()) {
            return response()->json(
                [
                    'status' => 'error',
                    'message' => 'Authentication failed.',
                    'details' => $authResponse->json(),
                ],
                401,
            );
        }

        $accessToken = $authResponse['AccessToken'] ?? null;
        if (!$accessToken) {
            return response()->json(
                [
                    'status' => 'error',
                    'message' => 'Access token not received.',
                    'auth_response' => $authResponse->json(),
                ],
                401,
            );
        }

        // Step 3: Build query parameters
        $queryParams = [];

        // Optional parameters from request
        if ($request->has('BusinessId') && !empty($request->get('BusinessId'))) {
            $queryParams['BusinessId'] = $request->get('BusinessId');
        }

        if ($request->has('EIN') && !empty($request->get('EIN'))) {
            $queryParams['EIN'] = $request->get('EIN');
        }

        if ($request->has('Page') && !empty($request->get('Page'))) {
            $queryParams['Page'] = $request->get('Page');
        } else {
            $queryParams['Page'] = 1; // Default page
        }

        if ($request->has('PageSize') && !empty($request->get('PageSize'))) {
            $queryParams['PageSize'] = $request->get('PageSize');
        } else {
            $queryParams['PageSize'] = 10; // Default page size
        }

        if ($request->has('EfileStatus') && !empty($request->get('EfileStatus'))) {
            $queryParams['EfileStatus'] = $request->get('EfileStatus');
        } else {
            $queryParams['EfileStatus'] = 'ALL'; // Default status
        }

        if ($request->has('FromDate') && !empty($request->get('FromDate'))) {
            $queryParams['FromDate'] = $request->get('FromDate');
        }

        if ($request->has('ToDate') && !empty($request->get('ToDate'))) {
            $queryParams['ToDate'] = $request->get('ToDate');
        }

        // Step 4: Make API request to list Form 941 returns
        $endpoint = $apiUrl . '/Form941/List';

        // Build the full URL with query parameters
        $url = $endpoint . '?' . http_build_query($queryParams);

        try {
            $listResponse = Http::withHeaders([
                'Authorization' => 'Bearer ' . $accessToken,
                'Accept' => 'application/json',
            ])->get($url);

            if ($listResponse->successful()) {
                $responseData = $listResponse->json();

                return response()->json([
                    'status' => 'success',
                    'message' => 'Form 941 returns retrieved successfully.',
                    'data' => $responseData,
                    'query_params' => $queryParams, // For debugging
                ]);
            } else {
                return response()->json(
                    [
                        'status' => 'error',
                        'message' => 'Failed to retrieve Form 941 returns from TaxBandits.',
                        'http_status' => $listResponse->status(),
                        'error_response' => $listResponse->json(),
                        'query_params' => $queryParams, // For debugging
                    ],
                    $listResponse->status(),
                );
            }
        } catch (\Exception $e) {
            return response()->json(
                [
                    'status' => 'error',
                    'message' => 'Exception occurred while listing Form 941 returns: ' . $e->getMessage(),
                ],
                500,
            );
        }
    }


    public function updateForm941(Request $request)
    {
        $request->validate([
            'updates' => 'required|array', 
        ]);
    
        $filingId = $request->query('id');

        try {
            $irsFiling = Filing::find($filingId);
            if (!$irsFiling) {
                return response()->json(['status' => 'error', 'message' => 'IRS Filing not found'], 404);
            }

            // Current form data from database
            $formData = json_decode($irsFiling->form_data, true) ?? [];
            $updates = $request->input('updates');
            
            // DEBUG: Log what we have
            \Log::info('Original form_data:', $formData);
            \Log::info('Updates received:', $updates);
            
            
            if (isset($formData['Form941Records'][0])) {
                // ReturnHeader merge
                if (isset($updates['ReturnHeader'])) {
                    $formData['Form941Records'][0]['ReturnHeader'] = 
                        $this->deepMerge(
                            $formData['Form941Records'][0]['ReturnHeader'] ?? [],
                            $updates['ReturnHeader']
                        );
                }
                
                // ReturnData merge  
                if (isset($updates['ReturnData'])) {
                    $formData['Form941Records'][0]['ReturnData'] = 
                        $this->deepMerge(
                            $formData['Form941Records'][0]['ReturnData'] ?? [],
                            $updates['ReturnData']
                        );
                }
            }
            
            \Log::info('After merge form_data:', $formData);

           
            $payload = [
                "SubmissionId" => $irsFiling->submission_id,
                "Form941Records" => [
                    [
                        "RecordId" => $irsFiling->record_id,
                        "SequenceId" => $formData['Form941Records'][0]['SequenceId'] ?? '001',
                        "ReturnHeader" => $formData['Form941Records'][0]['ReturnHeader'] ?? [],
                        "ReturnData" => $formData['Form941Records'][0]['ReturnData'] ?? [],
                    ]
                ]
            ];

            \Log::info('Final API Payload:', $payload);

           
            $clientId = config('services.taxbandits.client_id');
            $clientSecret = config('services.taxbandits.client_secret');
            $userToken = config('services.taxbandits.user_token');
            $apiUrl = rtrim(config('services.taxbandits.api_url'), '/');

            $jwtToken = $this->generateTaxBanditsJWT($clientId, $clientSecret, $userToken);

            $client = new \GuzzleHttp\Client();
            $response = $client->put($apiUrl . '/Form941/Update', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $jwtToken,
                    'Content-Type' => 'application/json',
                ],
                'json' => $payload,
            ]);

            $result = json_decode($response->getBody(), true);

            // Update database
            $irsFiling->update([
                'form_data' => json_encode($formData),
                'status' => 'UPDATED',
                'api_response' => json_encode($result),
            ]);

            // Log create
            IrsFilingLog::create([
                'filing_id' => $irsFiling->id,
                'action' => 'update',
                'request_data' => json_encode($payload),
                'response_data' => json_encode($result),
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Form 941 updated successfully',
                'data' => $result,
            ], 200);

        } catch (\Exception $e) {
            \Log::error('Update Error:', ['error' => $e->getMessage()]);
            return response()->json([
                'status' => 'error',
                'message' => 'Error during update: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Proper deep merge function
     */
    private function deepMerge(array $array1, array $array2): array
    {
        $merged = $array1;
        
        foreach ($array2 as $key => $value) {
            if (is_array($value) && isset($merged[$key]) && is_array($merged[$key])) {
                $merged[$key] = $this->deepMerge($merged[$key], $value);
            } else {
                $merged[$key] = $value;
            }
        }
        
        return $merged;
    }

    // public function updateForm941(Request $request)
    // {

    //     $filingId = $request->query('id');

    //     try {
    //         $irsFiling = Filing::find($filingId);
    //         if (!$irsFiling) {
    //             return response()->json(['status' => 'error', 'message' => 'IRS Filing not found'], 404);
    //         }

    //         $formData = json_decode($irsFiling->form_data, true) ?? [];

    //         // 1️⃣ Flat form-data from frontend
    //         $flatFields = $request->all();

    //         // 2️⃣ Map flat fields to nested structure dynamically
    //         $updates = [];

    //         foreach ($flatFields as $key => $value) {
    //             // Example rules: dynamic mapping
    //             if (str_starts_with($key, 'Business')) {
    //                 $updates['ReturnHeader']['Business'][$key] = $value;
    //             } else {
    //                 $updates['ReturnData']['Form941'][$key] = $value;
    //             }
    //         }

    //         \Log::info('Dynamic Updates:', $updates);

    //         // 3️⃣ Merge updates with existing form_data
    //         if (isset($formData['Form941Records'][0])) {
    //             if (isset($updates['ReturnHeader'])) {
    //                 $formData['Form941Records'][0]['ReturnHeader'] = $this->deepMerge(
    //                     $formData['Form941Records'][0]['ReturnHeader'] ?? [],
    //                     $updates['ReturnHeader']
    //                 );
    //             }
    //             if (isset($updates['ReturnData'])) {
    //                 $formData['Form941Records'][0]['ReturnData'] = $this->deepMerge(
    //                     $formData['Form941Records'][0]['ReturnData'] ?? [],
    //                     $updates['ReturnData']
    //                 );
    //             }
    //         }

    //         \Log::info('After merge form_data:', $formData);

    //         // 4️⃣ Build payload for TaxBandits
    //         $payload = [
    //             "SubmissionId" => $irsFiling->submission_id,
    //             "Form941Records" => [
    //                 [
    //                     "RecordId" => $irsFiling->record_id,
    //                     "SequenceId" => $formData['Form941Records'][0]['SequenceId'] ?? '001',
    //                     "ReturnHeader" => $formData['Form941Records'][0]['ReturnHeader'] ?? [],
    //                     "ReturnData" => $formData['Form941Records'][0]['ReturnData'] ?? [],
    //                 ]
    //             ]
    //         ];
    //     return $payload;
    //             // 5️⃣ Call TaxBandits API
    //             $jwtToken = $this->generateTaxBanditsJWT(
    //                 config('services.taxbandits.client_id'),
    //                 config('services.taxbandits.client_secret'),
    //                 config('services.taxbandits.user_token')
    //             );

    //             $client = new \GuzzleHttp\Client();
    //             $response = $client->put(rtrim(config('services.taxbandits.api_url'), '/') . '/Form941/Update', [
    //                 'headers' => [
    //                     'Authorization' => 'Bearer ' . $jwtToken,
    //                     'Content-Type' => 'application/json',
    //                 ],
    //                 'json' => $payload,
    //             ]);

    //             $result = json_decode($response->getBody(), true);

    //             // 6️⃣ Update DB & log
    //             $irsFiling->update([
    //                 'form_data' => json_encode($formData),
    //                 'status' => $result['StatusName'] ?? 'UPDATED',
    //                 'api_response' => json_encode($result),
    //             ]);

    //             IrsFilingLog::create([
    //                 'filing_id' => $irsFiling->id,
    //                 'action' => 'update',
    //                 'request_data' => json_encode($payload),
    //                 'response_data' => json_encode($result),
    //             ]);

    //             return response()->json([
    //                 'status' => 'success',
    //                 'message' => 'Form 941 updated successfully',
    //                 'data' => $result,
    //             ], 200);

    //         } catch (\Exception $e) {
    //             \Log::error('Update Error:', ['error' => $e->getMessage()]);
    //             return response()->json([
    //                 'status' => 'error',
    //                 'message' => 'Error during update: ' . $e->getMessage(),
    //             ], 500);
    //         }
    // }



        
    public function getForm941($id)
    {
        $filing = Filing::find($id);

        if (!$filing || !$filing->submission_id) {
            return response()->json([
                'status' => 'error',
                'message' => 'Filing record not found or missing Submission ID.',
            ], 404);
        }

        $submissionId = $filing->submission_id;

        $clientId = config('services.taxbandits.client_id');
        $clientSecret = config('services.taxbandits.client_secret');
        $userToken = config('services.taxbandits.user_token');
        $apiUrl = rtrim(config('services.taxbandits.api_url'), '/');

        if (!$clientId || !$clientSecret || !$userToken) {
            return response()->json([
                'status' => 'error',
                'message' => 'Missing TaxBandits credentials.',
            ], 400);
        }

        try {
            $jwtToken = $this->generateTaxBanditsJWT($clientId, $clientSecret, $userToken);

            $queryParams = ['SubmissionId' => $submissionId];
            $url = $apiUrl . '/Form941/Get?' . http_build_query($queryParams);

            $getResponse = Http::withHeaders([
                'Authorization' => 'Bearer ' . $jwtToken,
                'Accept' => 'application/json',
            ])->get($url);

            IrsFilingLog::create([
                'filing_id' => $filing->id,
                'action' => 'GetForm941',
                'request_data' => $queryParams,
                'response_data' => $getResponse->json(),
            ]);

            if ($getResponse->successful()) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Form 941 information retrieved successfully.',
                    'data' => $getResponse->json(),
                ]);
            }

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve Form 941 information from TaxBandits.',
                'http_status' => $getResponse->status(),
                'error_response' => $getResponse->json(),
            ], $getResponse->status());

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Exception occurred while retrieving Form 941 information: ' . $e->getMessage(),
            ], 500);
        }
    }

    private function generateTaxBanditsJWT($clientId, $clientSecret, $userToken)
    {
        // JWT Header
        $header = [
            'alg' => 'HS256',
            'typ' => 'JWT',
        ];

        // JWT Payload
        $payload = [
            'iss' => $clientId, // Issuer: Client ID
            'sub' => $clientId, // Subject: Client ID
            'aud' => $userToken, // Audience: User Token
            'iat' => time(),
            'exp' => time() + 300, // Issued at: Current timestamp
        ];

        // Encode header and payload
        $headerEncoded = $this->base64UrlEncode(json_encode($header));
        $payloadEncoded = $this->base64UrlEncode(json_encode($payload));

        // Create signature
        $signature = hash_hmac('sha256', $headerEncoded . '.' . $payloadEncoded, $clientSecret, true);
        $signatureEncoded = $this->base64UrlEncode($signature);

        // Return complete JWT
        return $headerEncoded . '.' . $payloadEncoded . '.' . $signatureEncoded;
    }

    /**
     * Base64 URL encode (without padding)
     */
    private function base64UrlEncode($data)
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    public function validateForm941JsonToTaxBandits(Request $request)
    {
        $data = $request->all();

        // Validate required fields
        if (!isset($data['Form941Records']) || !is_array($data['Form941Records'])) {
            return response()->json(
                [
                    'status' => 'error',
                    'message' => 'Form941Records array is required.',
                ],
                400,
            );
        }

        // Load TaxBandits credentials
        $clientId = config('services.taxbandits.client_id');
        $clientSecret = config('services.taxbandits.client_secret');
        $userToken = config('services.taxbandits.user_token');
        $authUrl = config('services.taxbandits.auth_url');
        $apiUrl = config('services.taxbandits.api_url');

        if (empty($clientId) || empty($clientSecret) || empty($userToken)) {
            return response()->json(
                [
                    'status' => 'error',
                    'message' => 'Missing TaxBandits credentials configuration.',
                ],
                500,
            );
        }

        // Generate JWT token
        try {
            $jwtToken = $this->generateTaxBanditsJWT($clientId, $clientSecret, $userToken);
        } catch (\Exception $e) {
            return response()->json(
                [
                    'status' => 'error',
                    'message' => 'Failed to generate JWT token: ' . $e->getMessage(),
                ],
                500,
            );
        }

        // Get access token
        $authResponse = Http::withHeaders([
            'Authentication' => $jwtToken,
        ])->get($authUrl);

        if ($authResponse->failed()) {
            return response()->json(
                [
                    'status' => 'error',
                    'message' => 'TaxBandits authentication failed.',
                    'details' => $authResponse->json(),
                ],
                401,
            );
        }

        $accessToken = $authResponse['AccessToken'] ?? null;
        if (!$accessToken) {
            return response()->json(
                [
                    'status' => 'error',
                    'message' => 'Failed to get access token from TaxBandits.',
                    'response' => $authResponse->json(),
                ],
                401,
            );
        }

        // Prepare payload with strict validation
        $payload = $this->prepareValidatedPayload($data);

        // Validate with TaxBandits
        $validateEndpoint = $apiUrl . '/Form941/ValidateForm';
        $validateResponse = Http::withHeaders([
            'Authorization' => 'Bearer ' . $accessToken,
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ])->post($validateEndpoint, $payload);

        if ($validateResponse->successful()) {
            return response()->json([
                'status' => 'success',
                'message' => 'Form 941 validated successfully.',
                'response' => $validateResponse->json(),
            ]);
        }

        return response()->json(
            [
                'status' => 'error',
                'message' => 'Validation failed.',
                'http_status' => $validateResponse->status(),
                'error_response' => $validateResponse->json(),
                'request_payload' => $payload,
            ],
            $validateResponse->status() ?: 400,
        );
    }

    private function prepareValidatedPayload($data)
    {
        $form941Records = [];

        foreach ($data['Form941Records'] as $record) {
            // Strict rounding function (2 decimal places)
            $round2 = function ($value) {
                if (!is_numeric($value)) {
                    return 0.0;
                }
                return round(floatval($value), 2);
            };

            $returnHeader = $record['ReturnHeader'] ?? [];
            $business = $returnHeader['Business'] ?? [];
            $returnData = $record['ReturnData'] ?? [];
            $form941 = $returnData['Form941'] ?? [];
            $depositSchedule = $returnData['DepositScheduleType'] ?? [];
            $form8974 = $form941['Form8974'] ?? null;

            // Validate EIN format (must be 9 digits, not test patterns)
            $ein = preg_replace('/[^0-9]/', '', $business['EINorSSN'] ?? '');
            if (strlen($ein) !== 9 || in_array($ein, ['123456789', '111111111', '222222222', '333333333', '444444444', '555555555', '666666666', '777777777', '888888888', '999999999'])) {
                $ein = '000000000'; // Fallback to default if invalid
            }

            // Calculate taxes with IRS-required formulas
            $socialSecurityWages = $round2($form941['SocialSecurityTaxCashWagesAmt_Col1'] ?? 0);
            $socialSecurityTips = $round2($form941['TaxableSocSecTipsAmt_Col1'] ?? 0);
            $medicareWages = $round2($form941['TaxableMedicareWagesTipsAmt_Col1'] ?? 0);
            $additionalMedicareWages = $round2($form941['TxblWageTipsSubjAddnlMedcrAmt_Col1'] ?? 0);

            // IRS-mandated calculations
            $socialSecurityTax = $round2(($socialSecurityWages + $socialSecurityTips) * 0.124);
            $medicareTax = $round2($medicareWages * 0.029);
            $additionalMedicareTax = $round2($additionalMedicareWages * 0.009);

            $totalTaxBeforeAdjustment = $round2(($form941['FedIncomeTaxWHAmt'] ?? 0) + $socialSecurityTax + $medicareTax + $additionalMedicareTax);

            // Handle Form 8974 if present
            $payrollTaxCreditAmt = $round2($form941['PayrollTaxCreditAmt'] ?? 0);
            if ($form8974) {
                $payrollTaxCreditAmt = $round2($form8974['Line17'] ?? $payrollTaxCreditAmt);
            }

            $totalTaxAfterAdjustment = $round2($form941['TotalTaxAfterAdjustmentAmt'] ?? $totalTaxBeforeAdjustment - $payrollTaxCreditAmt);

            // Handle deposit schedule validation
            $depositorType = $depositSchedule['DepositorType'] ?? 'NONE';
            $monthlyDepositor = $depositSchedule['MonthlyDepositor'] ?? null;
            $totalQuarterTaxLiability = $round2($depositSchedule['TotalQuarterTaxLiabilityAmt'] ?? 0);

            if ($totalTaxAfterAdjustment < 2500) {
                $depositorType = 'NONE';
                $monthlyDepositor = null;
                $totalQuarterTaxLiability = 0.0;
            } elseif ($depositorType === 'MONTHLY' && !$monthlyDepositor) {
                // Distribute tax liability evenly if not provided
                $perMonth = $round2($totalTaxAfterAdjustment / 3);
                $monthlyDepositor = [
                    'TaxLiabilityMonth1' => $perMonth,
                    'TaxLiabilityMonth2' => $perMonth,
                    'TaxLiabilityMonth3' => $round2($totalTaxAfterAdjustment - 2 * $perMonth),
                ];
                $totalQuarterTaxLiability = $totalTaxAfterAdjustment;
            }

            // Build validated record
            $form941Records[] = [
                'SequenceId' => $record['SequenceId'] ?? '001',
                'ReturnHeader' => $returnHeader,
                'ReturnData' => [
                    'Form941' => [
                        'EmployeeCnt' => intval($form941['EmployeeCnt'] ?? 0),
                        'WagesAmt' => $round2($form941['WagesAmt'] ?? 0),
                        'FedIncomeTaxWHAmt' => $round2($form941['FedIncomeTaxWHAmt'] ?? 0),
                        'WagesNotSubjToSSMedcrTaxInd' => $form941['WagesNotSubjToSSMedcrTaxInd'] ?? false,
                        'SocialSecurityTaxCashWagesAmt_Col1' => $socialSecurityWages,
                        'TaxableSocSecTipsAmt_Col1' => $socialSecurityTips,
                        'TaxableMedicareWagesTipsAmt_Col1' => $medicareWages,
                        'TxblWageTipsSubjAddnlMedcrAmt_Col1' => $additionalMedicareWages,
                        'SocialSecurityTaxAmt_Col2' => $socialSecurityTax,
                        'TaxOnSocialSecurityTipsAmt_Col2' => $round2($form941['TaxOnSocialSecurityTipsAmt_Col2'] ?? 0),
                        'TaxOnMedicareWagesTipsAmt_Col2' => $medicareTax,
                        'TaxOnWageTipsSubjAddnlMedcrAmt_Col2' => $additionalMedicareTax,
                        'TotSSMdcrTaxAmt' => $round2($socialSecurityTax + $medicareTax),
                        'TaxOnUnreportedTips3121qAmt' => $round2($form941['TaxOnUnreportedTips3121qAmt'] ?? 0),
                        'TotalTaxBeforeAdjustmentAmt' => $totalTaxBeforeAdjustment,
                        'CurrentQtrFractionsCentsAmt' => $round2($form941['CurrentQtrFractionsCentsAmt'] ?? 0),
                        'CurrentQuarterSickPaymentAmt' => $round2($form941['CurrentQuarterSickPaymentAmt'] ?? 0),
                        'CurrQtrTipGrpTermLifeInsAdjAmt' => $round2($form941['CurrQtrTipGrpTermLifeInsAdjAmt'] ?? 0),
                        'TotalTaxAfterAdjustmentAmt' => $totalTaxAfterAdjustment,
                        'PayrollTaxCreditAmt' => $payrollTaxCreditAmt,
                        'IsPayrollTaxCredit' => $form941['IsPayrollTaxCredit'] ?? false,
                        'Form8974' => $form8974,
                        'TotTaxAfterAdjustmentAndNonRfdCr' => $round2($form941['TotTaxAfterAdjustmentAndNonRfdCr'] ?? $totalTaxAfterAdjustment),
                        'TotTaxDepositAmt' => $round2($form941['TotTaxDepositAmt'] ?? 0),
                        'BalanceDueAmt' => $round2($form941['BalanceDueAmt'] ?? max($totalTaxAfterAdjustment - ($form941['TotTaxDepositAmt'] ?? 0), 0)),
                        'OverpaidAmt' => $round2($form941['OverpaidAmt'] ?? 0),
                        'OverPaymentRecoveryType' => $form941['OverPaymentRecoveryType'] ?? null,
                    ],
                    'IRSPaymentType' => $returnData['IRSPaymentType'] ?? 'EFTPS',
                    'DepositScheduleType' => [
                        'DepositorType' => $depositorType,
                        'MonthlyDepositor' => $monthlyDepositor,
                        'SemiWeeklyDepositor' => $depositSchedule['SemiWeeklyDepositor'] ?? null,
                        'TotalQuarterTaxLiabilityAmt' => $totalQuarterTaxLiability,
                    ],
                ],
            ];
        }

        return ['Form941Records' => $form941Records];
    }

    

    // public function validateSubmittedForm941(Request $request)
    // {
    //     $submissionId = $request->input('submission_id');
    //     $recordId = $request->input('record_id');

    //     if (!$submissionId || !$recordId) {
    //         return response()->json(
    //             [
    //                 'status' => 'error',
    //                 'message' => 'SubmissionId and RecordId are required.',
    //             ],
    //             400,
    //         );
    //     }

    //     // Load credentials and URLs
    //     $clientId = config('services.taxbandits.client_id');
    //     $clientSecret = config('services.taxbandits.client_secret');
    //     $userToken = config('services.taxbandits.user_token');
    //     $authUrl = config('services.taxbandits.auth_url');
    //     $apiUrl = config('services.taxbandits.api_url');

    //     // Generate JWT token
    //     try {
    //         $jwtToken = $this->generateTaxBanditsJWT($clientId, $clientSecret, $userToken);
    //     } catch (\Exception $e) {
    //         return response()->json(
    //             [
    //                 'status' => 'error',
    //                 'message' => 'Failed to generate JWT token: ' . $e->getMessage(),
    //             ],
    //             500,
    //         );
    //     }

    //     // Authenticate to get Access Token
    //     $authResponse = Http::withHeaders([
    //         'Authentication' => $jwtToken,
    //     ])->get($authUrl);

    //     if ($authResponse->failed()) {
    //         return response()->json(
    //             [
    //                 'status' => 'error',
    //                 'message' => 'Authentication failed.',
    //                 'details' => $authResponse->json(),
    //             ],
    //             401,
    //         );
    //     }

    //     $accessToken = $authResponse['AccessToken'] ?? null;
    //     if (!$accessToken) {
    //         return response()->json(
    //             [
    //                 'status' => 'error',
    //                 'message' => 'Access token not received.',
    //                 'auth_response' => $authResponse->json(),
    //             ],
    //             401,
    //         );
    //     }

    //     // Build Validate API endpoint with query parameters
    //     $endpoint = $apiUrl . '/Form941/Validate';
    //     $queryParams = [
    //         'submissionId' => $submissionId,
    //         'recordIds' => $recordId,
    //     ];

    //     // Call the Validate API
    //     $response = Http::withHeaders([
    //         'Authorization' => 'Bearer ' . $accessToken,
    //         'Accept' => 'application/json',
    //     ])->get($endpoint, $queryParams);

    //     if ($response->successful()) {
    //         return response()->json([
    //             'status' => 'success',
    //             'message' => 'Validation data retrieved successfully.',
    //             'data' => $response->json(),
    //         ]);
    //     } else {
    //         return response()->json([
    //             'status' => 'error',
    //             'message' => 'Failed to get validation data from TaxBandits.',
    //             'http_status' => $response->status(),
    //             'raw_body' => $response->body(),
    //         ]);
    //     }
    // }

    // public function validateSubmittedForm941(Request $request)
    // {
    //     $submissionId = $request->input('submission_id');
    //     $recordId     = $request->input('record_id');

    //     if (!$submissionId || !$recordId) {
    //         return response()->json([
    //             'status' => 'error',
    //             'message' => 'SubmissionId and RecordId are required.',
    //         ], 400);
    //     }

    //     // Find the filing record
    //     $filing = Filing::where('submission_id', $submissionId)
    //                     ->where('record_id', $recordId)
    //                     ->first();

    //     if (!$filing) {
    //         return response()->json([
    //             'status' => 'error',
    //             'message' => 'Filing record not found.',
    //         ], 404);
    //     }

    //     // Load TaxBandits credentials
    //     $clientId     = config('services.taxbandits.client_id');
    //     $clientSecret = config('services.taxbandits.client_secret');
    //     $userToken    = config('services.taxbandits.user_token');
    //     $authUrl      = config('services.taxbandits.auth_url');
    //     $apiUrl       = config('services.taxbandits.api_url');

    //     // Generate JWT token
    //     try {
    //         $jwtToken = $this->generateTaxBanditsJWT($clientId, $clientSecret, $userToken);
    //     } catch (\Exception $e) {
    //         return response()->json([
    //             'status' => 'error',
    //             'message' => 'Failed to generate JWT token: ' . $e->getMessage(),
    //         ], 500);
    //     }

    //     // Authenticate to get Access Token
    //     $authResponse = Http::withHeaders([
    //         'Authentication' => $jwtToken,
    //     ])->get($authUrl);

    //     if ($authResponse->failed()) {
    //         return response()->json([
    //             'status' => 'error',
    //             'message' => 'Authentication failed.',
    //             'details' => $authResponse->json(),
    //         ], 401);
    //     }

    //     $accessToken = $authResponse['AccessToken'] ?? null;
    //     if (!$accessToken) {
    //         return response()->json([
    //             'status' => 'error',
    //             'message' => 'Access token not received.',
    //             'auth_response' => $authResponse->json(),
    //         ], 401);
    //     }

    //     // Build Validate API endpoint with query parameters
    //     $endpoint = $apiUrl . '/Form941/Validate';
    //     $queryParams = [
    //         'submissionId' => $submissionId,
    //         'recordIds'    => $recordId,
    //     ];

    //     // Call the Validate API
    //     $response = Http::withHeaders([
    //         'Authorization' => 'Bearer ' . $accessToken,
    //         'Accept'        => 'application/json',
    //     ])->get($endpoint, $queryParams);

    //     // Save log and return response
    //     $responseData = $response->successful() ? $response->json() : $response->body();
    //     IrsFilingLog::create([
    //         'filing_id'     => $filing->id,
    //         'action'        => $response->successful() ? 'validate' : 'validate_failed',
    //         'request_data'  => $queryParams,
    //         'response_data' => $responseData,
    //     ]);

    //     if ($response->successful()) {
    //         return response()->json([
    //             'status'  => 'success',
    //             'message' => 'Validation data retrieved successfully.',
    //             'data'    => $responseData,
    //         ]);
    //     } else {
    //         return response()->json([
    //             'status'      => 'error',
    //             'message'     => 'Failed to get validation data from TaxBandits.',
    //             'http_status' => $response->status(),
    //             'raw_body'    => $responseData,
    //         ]);
    //     }
    // }
    public function validateSubmittedForm941(Request $request)
    {
        $filingId = $request->input('id');

        if (!$filingId) {
            return response()->json([
                'status' => 'error',
                'message' => 'filing_id is required.',
            ], 400);
        }

        // Find the filing record from DB
        $filing = Filing::find($filingId);

        if (!$filing || !$filing->submission_id || !$filing->record_id) {
            return response()->json([
                'status' => 'error',
                'message' => 'Filing record not found or missing Submission/Record ID.',
            ], 404);
        }

        $submissionId = $filing->submission_id;
        $recordId     = $filing->record_id;

        // Load TaxBandits credentials
        $clientId     = config('services.taxbandits.client_id');
        $clientSecret = config('services.taxbandits.client_secret');
        $userToken    = config('services.taxbandits.user_token');
        $authUrl      = config('services.taxbandits.auth_url');
        $apiUrl       = config('services.taxbandits.api_url');

        // Generate JWT token
        try {
            $jwtToken = $this->generateTaxBanditsJWT($clientId, $clientSecret, $userToken);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to generate JWT token: ' . $e->getMessage(),
            ], 500);
        }

        // Authenticate to get Access Token
            $authResponse = Http::withHeaders([
                'Authentication' => $jwtToken,
            ])->get($authUrl);


        if ($authResponse->failed()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Authentication failed.',
                'details' => $authResponse->json(),
            ], 401);
        }

        $accessToken = $authResponse['AccessToken'] ?? null;
        if (!$accessToken) {
            return response()->json([
                'status' => 'error',
                'message' => 'Access token not received.',
                'auth_response' => $authResponse->json(),
            ], 401);
        }

        // Build Validate API endpoint with query parameters
        $endpoint = $apiUrl . '/Form941/Validate';
        $queryParams = [
            'SubmissionId' => $submissionId,
            'RecordIds'    => $recordId,
        ];

        // Call the Validate API (GET call)
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $accessToken,
            'Accept'        => 'application/json',
        ])->get($endpoint, $queryParams);

        // Save log and return response
        $responseData = $response->successful() ? $response->json() : $response->body();
        

        IrsFilingLog::create([
            'filing_id'     => $filing->id,
            'action'        => $response->successful() ? 'validate' : 'validate_failed',
            'request_data'  => json_encode($queryParams),
            'response_data' => json_encode($responseData),
        ]);

        if ($response->successful()) {
            $filing->status = 'Validated'; 
            $filing->save();
            return response()->json([
                'status'  => 'success',
                'message' => 'Validation data retrieved successfully.',
                'data'    => $responseData,
            ]);
        } else {
            return response()->json([
                'status'      => 'error',
                'message'     => 'Failed to get validation data from TaxBandits.',
                'http_status' => $response->status(),
                'raw_body'    => $responseData,
            ]);
        }
    }


    public function getForm941Pdf($id)
    {
        $filing = Filing::find($id);

        if (!$filing || !$filing->submission_id || !$filing->record_id) {
            return response()->json([
                'status' => 'error',
                'message' => 'Filing record not found or missing Submission ID.',
            ], 404);
        }

        $submissionId = $filing->submission_id;
        $recordId = $filing->record_id;

        if (!$submissionId || !$recordId) {
            return response()->json(
                [
                    'status' => 'error',
                    'message' => 'SubmissionId and RecordId are required.',
                ],
                400,
            );
        }

        // Load credentials
        $clientId = config('services.taxbandits.client_id');
        $clientSecret = config('services.taxbandits.client_secret');
        $userToken = config('services.taxbandits.user_token');
        $authUrl = config('services.taxbandits.auth_url');
        $apiUrl = config('services.taxbandits.api_url');

        // Generate JWT and get access token
        $jwtToken = $this->generateTaxBanditsJWT($clientId, $clientSecret, $userToken);
        $authResponse = Http::withHeaders([
            'Authentication' => $jwtToken,
        ])->get($authUrl);

        if ($authResponse->failed() || empty($authResponse['AccessToken'])) {
            return response()->json(
                [
                    'status' => 'error',
                    'message' => 'Failed to authenticate with TaxBandits.',
                    'details' => $authResponse->json(),
                ],
                401,
            );
        }

        $accessToken = $authResponse['AccessToken'];

        // Make the PDF request
        $endpoint = $apiUrl . '/Form941/GetPDF';
        $params = [
            'SubmissionId' => $submissionId,
            'RecordId' => $recordId,
        ];

        $pdfResponse = Http::withHeaders([
            'Authorization' => 'Bearer ' . $accessToken,
            'Accept' => 'application/json',
        ])->get($endpoint, $params);

         IrsFilingLog::create([
            'filing_id'     => $filing->id,
            'action'        => 'PDF Generate',
            'request_data'  => json_encode(['endpoint' => $endpoint]),
            'response_data' => json_encode($params),
        ]);

        if ($pdfResponse->successful()) {
            return response()->json([
                'status' => 'generated',
                'message' => 'PDF generation requested successfully.',
                'generation_complete' => true,
                'response' => $pdfResponse->json(),
            ]);
        } else {
            return response()->json(
                [
                    'status' => 'error',
                    'message' => 'Failed to request PDF generation',
                    'errors' => $pdfResponse->json(),
                ],
                $pdfResponse->status(),
            );
        }
    }
    private function getTaxBanditsAccessToken()
    {
        $authUrl = rtrim(config('services.taxbandits.auth_url'), '/');
        $clientId = config('services.taxbandits.client_id');
        $clientSecret = config('services.taxbandits.client_secret');
        $userToken = config('services.taxbandits.user_token');

        $response = Http::asForm()->post("{$authUrl}/token", [
            'grant_type' => 'client_credentials',
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
            'UserToken' => $userToken,
        ]);

        if ($response->successful()) {
            $data = $response->json();
            return $data['access_token'] ?? null;
        }

        // Log error if needed
        \Log::error('Failed to get TaxBandits access token', ['response' => $response->json()]);

        return null;
    }
    // public function handlePdfWebhook(Request $request)
    // {
    //     Log::info('TaxBandits PDF Webhook received', $request->all());
    //     return response()->json(['status' => 'success']);
    // }

    public function handlePdfWebhook(Request $request)
    {
        $data = $request->all();

        Log::info('PDF Webhook received:', $data);

        $submissionId = $data['SubmissionId'] ?? null;
        $records = $data['Records'] ?? [];

        $recordId = null;
        $pdfUrl = null;

        if (!empty($records) && isset($records[0]['RecordId'])) {
            $recordId = $records[0]['RecordId'];
            $pdfUrl = $records[0]['StitchedPDFFilePath'] ?? null;
        }

        Log::info('Saving PDF webhook (with updateOrCreate):', [
            'submission_id' => $submissionId,
            'record_id' => $recordId,
            'pdf_url' => $pdfUrl,
        ]);

        TaxbanditsPdfWebhook::updateOrCreate(
            [
                'submission_id' => $submissionId,
                'record_id' => $recordId,
            ],
            [
                'pdf_url' => $pdfUrl,
                'payload' => $data,
            ],
        );

        return response()->json(['status' => 'success'],200);
    }

    // public function handlePdfWebhook(Request $request)
    // {
    //     try {
    //         $data = $request->all();

    //         Log::info('TaxBandits PDF Webhook received:', ['data' => $data]);

    //         $submissionId = $data['SubmissionId'] ?? null;
    //         $records = $data['Form941pdfRecords'] ?? [];

    //         if (empty($submissionId) || empty($records) || !isset($records[0]['RecordId'])) {
    //             Log::warning('Webhook data missing required fields', $data);
    //             return response()->json(['status' => 'error', 'message' => 'Missing required fields'], 422);
    //         }

    //         $recordId = $records[0]['RecordId'];
    //         $pdfUrl = $records[0]['PdfUrl'] ?? null;

    //         TaxbanditsPdfWebhook::updateOrCreate(
    //             ['submission_id' => $submissionId, 'record_id' => $recordId],
    //             ['pdf_url' => $pdfUrl, 'payload' => $data]
    //         );

    //         return response()->json(['status' => 'success']);
    //     } catch (\Exception $e) {
    //         Log::error('Error handling TaxBandits webhook', [
    //             'exception' => $e->getMessage(),
    //             'trace' => $e->getTraceAsString()
    //         ]);

    //         return response()->json(['status' => 'error', 'message' => 'Internal server error'], 500);
    //     }
    // }

    private function determineQuarter($fromDate)
    {
        $month = Carbon::parse($fromDate)->month;
        if ($month <= 3) {
            return 'Q1';
        } elseif ($month <= 6) {
            return 'Q2';
        } elseif ($month <= 9) {
            return 'Q3';
        } else {
            return 'Q4';
        }
    }

    // public function transmitForm941(Request $request)
    // {
    //     $request->validate([
    //         'submission_id' => 'required|uuid',
    //         'record_id' => 'required|array',
    //         'record_id.*' => 'uuid',
    //     ]);

    //     $submissionId = $request->input('submission_id');
    //     $recordIds = $request->input('record_id');

    //     $clientId = config('services.taxbandits.client_id');
    //     $clientSecret = config('services.taxbandits.client_secret');
    //     $userToken = config('services.taxbandits.user_token');
    //     $apiUrl = rtrim(config('services.taxbandits.api_url'), '/');

    //     $jwt = $this->generateTaxBanditsJWT($clientId, $clientSecret, $userToken);

    //     $responses = [];

    //     foreach ($recordIds as $recordId) {
    //         try {
    //             $payload = [
    //                 'SubmissionId' => $submissionId,
    //                 'RecordIds' => [$recordId],
    //             ];

    //             $response = Http::withHeaders([
    //                 'Authorization' => 'Bearer ' . $jwt,
    //                 'ClientId' => $clientId,
    //             ])->post($apiUrl . '/Form941/Transmit', $payload);

    //             $resBody = $response->json();

    //             $existingAttempts = IrsTransmission::where('submission_id', $submissionId)->where('record_id', $recordId)->value('attempts') ?? 0;

    //             $transmission = IrsTransmission::updateOrCreate(
    //                 [
    //                     'submission_id' => $submissionId,
    //                     'record_id' => $recordId,
    //                 ],
    //                 [
    //                     'status' => $response->successful() ? 'success' : 'failed',
    //                     'transmitted_at' => $response->successful() ? now() : null,
    //                     'error_message' => $response->successful() ? null : json_encode($resBody['ErrorMessage'] ?? $resBody),
    //                     'response' => $resBody,
    //                     'attempts' => $existingAttempts + 1,
    //                 ],
    //             );

    //             $responses[] = [
    //                 'record_id' => $recordId,
    //                 'status' => $transmission->status,
    //                 'response' => $resBody,
    //             ];
    //         } catch (\Exception $e) {
    //             $transmission = IrsTransmission::firstOrNew([
    //                 'submission_id' => $submissionId,
    //                 'record_id' => $recordId,
    //             ]);

    //             $transmission->status = 'failed';
    //             $transmission->error_message = $e->getMessage();
    //             $transmission->response = null;
    //             $transmission->attempts = ($transmission->attempts ?? 0) + 1;
    //             $transmission->save();

    //             $responses[] = [
    //                 'record_id' => $recordId,
    //                 'status' => 'failed',
    //                 'error' => $e->getMessage(),
    //             ];
    //         }
    //     }

    //     return response()->json([
    //         'message' => 'Transmission attempt complete',
    //         'results' => $responses,
    //     ]);
    // }

    public function transmitForm941($id)
    {
        $filing = Filing::findOrFail($id);
        $companyId = auth()->user()->company_id;

        if (!$filing->submission_id || !$filing->record_id) {
            return response()->json([
                'message' => 'Filing does not have submission_id or record_id',
            ], 422);
        }

        $submissionId = $filing->submission_id;
        $recordIds = is_array($filing->record_id) ? $filing->record_id : [$filing->record_id];

        $clientId = config('services.taxbandits.client_id');
        $clientSecret = config('services.taxbandits.client_secret');
        $userToken = config('services.taxbandits.user_token');
        $apiUrl = rtrim(config('services.taxbandits.api_url'), '/');

        $jwt = $this->generateTaxBanditsJWT($clientId, $clientSecret, $userToken);

        $responses = [];
        $hasError = false;

        foreach ($recordIds as $recordId) {
            try {
                $payload = [
                    'SubmissionId' => $submissionId,
                    'RecordIds' => [$recordId],
                ];

                $response = Http::withHeaders([
                    'Authorization' => 'Bearer ' . $jwt,
                    'ClientId' => $clientId,
                    'Content-Type' => 'application/json',
                ])->post($apiUrl . '/Form941/Transmit', $payload);

                $resBody = $response->json();

                // Check JSON StatusCode instead of HTTP status
                if (isset($resBody['StatusCode']) && $resBody['StatusCode'] == 200) {
                    $status = 'success';
                    $filing->status = 'Transmitted';
                } else {
                    $status = 'failed';
                    $filing->status = 'Failed';
                    $hasError = true;
                }

                $filing->api_response = $resBody;
                $filing->save();

                IrsFilingLog::create([
                    'filing_id' => $filing->id,
                    'action' => 'Transmit',
                    'request_data' => $payload,
                    'response_data' => $resBody,
                ]);

                $responses[] = [
                    'record_id' => $recordId,
                    'status' => $status,
                    'response' => $resBody,
                ];

            } catch (\Exception $e) {
                $hasError = true;

                IrsFilingLog::create([
                    'filing_id' => $filing->id,
                    'action' => 'TransmitFailed',
                    'request_data' => ['SubmissionId' => $submissionId, 'RecordId' => $recordId],
                    'response_data' => ['error' => $e->getMessage()],
                ]);

                $responses[] = [
                    'record_id' => $recordId,
                    'status' => 'failed',
                    'error' => $e->getMessage(),
                ];
            }
        }

        return response()->json([
            'message' => 'Transmission attempt complete',
            'filing_id' => $filing->id,
            'results' => $responses,
        ], $hasError ? 400 : 200);
    }


    public function uploadForm8453EMP(Request $request)
    {
        $id= $request->id;
        
         $filing = Filing::find($id);

        if (!$filing || !$filing->record_id) {
            return response()->json([
                'status' => 'error',
                'message' => 'Filing record not found or missing Submission ID.',
            ], 404);
        }
        $recordId = $filing-> record_id;
        
        $clientId = config('services.taxbandits.client_id');
        $clientSecret = config('services.taxbandits.client_secret');
        $userToken = config('services.taxbandits.user_token');
        $authUrl = rtrim(config('services.taxbandits.auth_url'), '/');
        $apiUrl = rtrim(config('services.taxbandits.api_url'), '/');

        // Step 1: Generate JWT Token
        try {
            $jwtToken = $this->generateTaxBanditsJWT($clientId, $clientSecret, $userToken);
        } catch (\Exception $e) {
            return response()->json(
                [
                    'status' => 'error',
                    'message' => 'JWT token generation failed: ' . $e->getMessage(),
                ],
                500,
            );
        }

        // Step 2: Authenticate (same as getForm941Pdf)
        $authResponse = Http::withHeaders([
            'Authentication' => $jwtToken,
        ])->get($authUrl);

        if ($authResponse->failed() || empty($authResponse['AccessToken'])) {
            return response()->json(
                [
                    'status' => 'error',
                    'message' => 'Failed to authenticate with TaxBandits.',
                    'details' => $authResponse->json(),
                ],
                401,
            );
        }

        $accessToken = $authResponse['AccessToken'];

        // Step 3: Validate input
        $request->validate([
            'pdf_file' => 'required|file|mimes:pdf|max:2048',
        ]);

        // Step 4: Read and encode PDF to base64
        $pdfFile = $request->file('pdf_file');
        $pdfContent = file_get_contents($pdfFile->getRealPath());
        $base64Pdf = base64_encode($pdfContent);

        // Step 5: Prepare payload exactly as per TaxBandits documentation
        $payload = [
            'RecordId' => $recordId,
            'Form8453EMPPdf' => $base64Pdf,
        ];

        // Step 6: Upload request
        $response = Http::withToken($accessToken)
            ->withHeaders([
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ])
            ->post($apiUrl . '/Form941/UploadForm8453EMP', $payload);

        if ($response->failed()) {
            return response()->json(
                [
                    'status' => 'error',
                    'message' => 'Upload failed',
                    'response' => $response->json(),
                ],
                400,
            );
        }

        return response()->json([
            'status' => 'success',
            'message' => '8453EMP uploaded successfully',
            'data' => $response->json(),
        ]);
    }

    public function downloadForm8453EMP($id)
    {
       $filing = Filing::find($id);

        if (!$filing || !$filing->record_id) {
            return response()->json([
                'status' => 'error',
                'message' => 'Filing record not found or missing Submission ID.',
            ], 404);
        }

        $recordId = $filing->record_id;


        $clientId = config('services.taxbandits.client_id');
        $clientSecret = config('services.taxbandits.client_secret');
        $userToken = config('services.taxbandits.user_token');
        $authUrl = rtrim(config('services.taxbandits.auth_url'), '/');
        $apiUrl = rtrim(config('services.taxbandits.api_url'), '/');

        try {
            $jwtToken = $this->generateTaxBanditsJWT($clientId, $clientSecret, $userToken);
        } catch (\Exception $e) {
            return response()->json(
                [
                    'status' => 'error',
                    'message' => 'JWT token generation failed: ' . $e->getMessage(),
                ],
                500,
            );
        }

        $authResponse = Http::withHeaders([
            'Authentication' => $jwtToken,
        ])->get($authUrl);

        if ($authResponse->failed() || empty($authResponse['AccessToken'])) {
            return response()->json(
                [
                    'status' => 'error',
                    'message' => 'Failed to authenticate with TaxBandits.',
                    'details' => $authResponse->json(),
                ],
                401,
            );
        }

        $accessToken = $authResponse['AccessToken'];
        // API Endpoint
        $endpoint = "{$apiUrl}/Form941/DownloadForm8453EMP";
        $params   = ['RecordId' => $recordId];

            // API Call
        $response = Http::withToken($accessToken)
            ->acceptJson()
            ->get($endpoint, $params);

        $responseData = $response->json();

        // ✅ Save logs (Request + Response)
        IrsFilingLog::create([
            'filing_id'     => $filing->id,
            'action'        => 'PDF Dawnload',
            'request_data'  => json_encode(['endpoint' => $endpoint, 'params' => $params]),
            'response_data' => json_encode($responseData),
        ]);


        if ($response->failed() || empty($responseData)) {
            return response()->json(
                [
                    'status' => 'error',
                    'message' => 'Failed to download Form 8453 EMP',
                    'data' => $responseData,
                ],
                400,
            );
        }

        // Check for API errors inside the response JSON
        if (!empty($responseData['Errors'])) {
            return response()->json(
                [
                    'status' => 'error',
                    'message' => 'API returned errors',
                    'errors' => $responseData['Errors'],
                ],
                400,
            );
        }

        if (empty($responseData['Form8453EMPPdf']) || strlen($responseData['Form8453EMPPdf']) < 50) {
            return response()->json(
                [
                    'status' => 'error',
                    'message' => 'Invalid or empty PDF data returned from API',
                    'data' => $responseData,
                ],
                404,
            );
        }

        $pdfBase64 = $responseData['Form8453EMPPdf'];
        $pdfBinary = base64_decode($pdfBase64);
        $fileName = "Form8453EMP_{$recordId}.pdf";

        return response($pdfBinary)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'inline; filename="' . $fileName . '"');
    }

    // public function downloadForm8453EMP(Request $request)
    // {
    //     $request->validate([
    //         'record_id' => 'required|uuid',
    //     ]);
    //     $recordId = $request->record_id;

    //     $clientId     = config('services.taxbandits.client_id');
    //     $clientSecret = config('services.taxbandits.client_secret');
    //     $userToken    = config('services.taxbandits.user_token');
    //     $authUrl      = rtrim(config('services.taxbandits.auth_url'), '/');
    //     $apiUrl       = rtrim(config('services.taxbandits.api_url'), '/');

    //     try {
    //         $jwtToken = $this->generateTaxBanditsJWT($clientId, $clientSecret, $userToken);
    //     } catch (\Exception $e) {
    //         return response()->json([
    //             'status'  => 'error',
    //             'message' => 'JWT token generation failed: ' . $e->getMessage(),
    //         ], 500);
    //     }

    //     $authResponse = Http::withHeaders([
    //         'Authentication' => $jwtToken,
    //     ])->get($authUrl);

    //     if ($authResponse->failed() || empty($authResponse['AccessToken'])) {
    //         return response()->json([
    //             'status'  => 'error',
    //             'message' => 'Failed to authenticate with TaxBandits.',
    //             'details' => $authResponse->json(),
    //         ], 401);
    //     }

    //     $accessToken = $authResponse['AccessToken'];

    //     $response = Http::withToken($accessToken)
    //         ->acceptJson()
    //         ->get("{$apiUrl}/Form941/DownloadForm8453EMP", [
    //             'RecordId' => $recordId,
    //         ]);

    //     $responseData = $response->json();

    //     if ($response->failed() || empty($responseData)) {
    //         return response()->json([
    //             'status'  => 'error',
    //             'message' => 'Failed to download Form 8453 EMP',
    //             'data'    => $responseData,
    //         ], 400);
    //     }

    //     if (!empty($responseData['Errors'])) {
    //         return response()->json([
    //             'status' => 'error',
    //             'message' => 'API returned errors',
    //             'errors' => $responseData['Errors'],
    //         ], 400);
    //     }

    //     return response()->json($responseData);
    // }

    public function downloadForm8879EM(Request $request)
    {
        $recordId = $request->query('record_id');

        if (!$recordId) {
            return response()->json(
                [
                    'status' => 'error',
                    'message' => 'RecordId parameter is required',
                ],
                400,
            );
        }

        $clientId = config('services.taxbandits.client_id');
        $clientSecret = config('services.taxbandits.client_secret');
        $userToken = config('services.taxbandits.user_token');
        $authUrl = rtrim(config('services.taxbandits.auth_url'), '/');
        $apiUrl = rtrim(config('services.taxbandits.api_url'), '/');

        try {
            $jwtToken = $this->generateTaxBanditsJWT($clientId, $clientSecret, $userToken);
        } catch (\Exception $e) {
            return response()->json(
                [
                    'status' => 'error',
                    'message' => 'JWT token generation failed: ' . $e->getMessage(),
                ],
                500,
            );
        }

        $authResponse = Http::withHeaders([
            'Authentication' => $jwtToken,
        ])->get($authUrl);

        if ($authResponse->failed() || empty($authResponse['AccessToken'])) {
            return response()->json(
                [
                    'status' => 'error',
                    'message' => 'Failed to authenticate with TaxBandits.',
                    'details' => $authResponse->json(),
                ],
                401,
            );
        }

        $accessToken = $authResponse['AccessToken'];

        $response = Http::withToken($accessToken)
            ->acceptJson()
            ->get("{$apiUrl}/Form941/DownloadForm8879EMP", [
                'RecordId' => $recordId,
            ]);

        if ($response->failed()) {
            return response()->json(
                [
                    'status' => 'error',
                    'message' => 'Failed to download Form 8879 EMP',
                    'data' => $response->json(),
                ],
                400,
            );
        }

        $pdfBase64 = $response->json('Form8879EMPPdf');

        if (!$pdfBase64) {
            return response()->json(
                [
                    'status' => 'error',
                    'message' => 'No PDF data found in API response',
                ],
                404,
            );
        }

        $pdfBinary = base64_decode($pdfBase64);

        $fileName = "Form8879EMP_{$recordId}.pdf";
        Storage::disk('local')->put($fileName, $pdfBinary);

        return response()->download(storage_path("app/{$fileName}"));
    }

    public function downloadForm8879EMP(Request $request)
    {
        $recordId = $request->query('recordId'); // GET parameter

        if (!$recordId) {
            return response()->json(
                [
                    'status' => 'error',
                    'message' => 'RecordId is required.',
                ],
                400,
            );
        }

        // Load credentials
        $clientId = config('services.taxbandits.client_id');
        $clientSecret = config('services.taxbandits.client_secret');
        $userToken = config('services.taxbandits.user_token');
        $authUrl = config('services.taxbandits.auth_url');
        $apiUrl = config('services.taxbandits.api_url');

        if (empty($userToken) || empty($clientId) || empty($clientSecret)) {
            return response()->json(
                [
                    'status' => 'error',
                    'message' => 'Missing TaxBandits credentials.',
                ],
                400,
            );
        }

        // Step 1: Generate JWT token
        try {
            $jwtToken = $this->generateTaxBanditsJWT($clientId, $clientSecret, $userToken);
        } catch (\Exception $e) {
            return response()->json(
                [
                    'status' => 'error',
                    'message' => 'Failed to generate JWT token: ' . $e->getMessage(),
                ],
                500,
            );
        }

        // Step 2: Get Access Token
        $authResponse = Http::withHeaders([
            'Authentication' => $jwtToken,
        ])->get($authUrl);

        if ($authResponse->failed()) {
            return response()->json(
                [
                    'status' => 'error',
                    'message' => 'Authentication failed.',
                    'details' => $authResponse->json(),
                ],
                401,
            );
        }

        $accessToken = $authResponse['AccessToken'] ?? null;
        if (!$accessToken) {
            return response()->json(
                [
                    'status' => 'error',
                    'message' => 'Access token not received.',
                ],
                401,
            );
        }

        // Step 3: Download Form 8879EMP
        $endpoint = $apiUrl . '/Form941/DownloadForm8879EMP?RecordId=' . $recordId;

        $downloadResponse = Http::withHeaders([
            'Authorization' => 'Bearer ' . $accessToken,
            'Accept' => 'application/json',
        ])->get($endpoint);

        if ($downloadResponse->successful()) {
            $responseData = $downloadResponse->json();

            // Check if PDF data exists
            if (isset($responseData['Form8879EMPPdf'])) {
                $pdfBase64 = $responseData['Form8879EMPPdf'];

                // Convert base64 to PDF file
                $pdfContent = base64_decode($pdfBase64);

                // Option 1: Return PDF directly for download
                return response($pdfContent)
                    ->header('Content-Type', 'application/pdf')
                    ->header('Content-Disposition', 'attachment; filename="Form8879EMP_' . $recordId . '.pdf"');

                // Option 2: Save to storage and return path (uncomment if needed)
                /*
                    $fileName = 'Form8879EMP_' . $recordId . '.pdf';
                    $filePath = storage_path('app/public/tax_forms/' . $fileName);
                    
                    // Create directory if not exists
                    if (!file_exists(dirname($filePath))) {
                        mkdir(dirname($filePath), 0755, true);
                    }
                    
                    file_put_contents($filePath, $pdfContent);
                    
                    return response()->json([
                        'status' => 'success',
                        'message' => 'Form 8879EMP downloaded successfully.',
                        'file_path' => '/storage/tax_forms/' . $fileName,
                        'download_url' => asset('storage/tax_forms/' . $fileName)
                    ]);
                    */
            } else {
                return response()->json(
                    [
                        'status' => 'error',
                        'message' => 'PDF data not found in response.',
                        'response' => $responseData,
                    ],
                    400,
                );
            }
        } else {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to download Form 8879EMP.',
                'http_status' => $downloadResponse->status(),
                'response' => $downloadResponse->json(),
            ]);
        }
    }
    // public function deleteForm941(Request $request)
    // {
    //     // Load credentials
    //     $clientId = config('services.taxbandits.client_id');
    //     $clientSecret = config('services.taxbandits.client_secret');
    //     $userToken = config('services.taxbandits.user_token');
    //     $authUrl = config('services.taxbandits.auth_url');
    //     $apiUrl = config('services.taxbandits.api_url');

    //     if (empty($userToken) || empty($clientId) || empty($clientSecret)) {
    //         return response()->json([
    //             'status' => 'error',
    //             'message' => 'Missing TaxBandits credentials.',
    //         ], 400);
    //     }

    //     // Validate required parameters
    //     $submissionId = $request->get('SubmissionId');
    //     if (empty($submissionId)) {
    //         return response()->json([
    //             'status' => 'error',
    //             'message' => 'SubmissionId parameter is required for delete operation.',
    //         ], 400);
    //     }

    //     // Step 1: Generate JWT token
    //     try {
    //         $jwtToken = $this->generateTaxBanditsJWT($clientId, $clientSecret, $userToken);
    //     } catch (\Exception $e) {
    //         return response()->json([
    //             'status' => 'error',
    //             'message' => 'Failed to generate JWT token: ' . $e->getMessage(),
    //         ], 500);
    //     }

    //     // Step 2: Get Access Token
    //     $authResponse = Http::withHeaders([
    //         'Authentication' => $jwtToken,
    //     ])->get($authUrl);

    //     if ($authResponse->failed()) {
    //         return response()->json([
    //             'status' => 'error',
    //             'message' => 'Authentication failed.',
    //             'details' => $authResponse->json(),
    //         ], 401);
    //     }

    //     $accessToken = $authResponse['AccessToken'] ?? null;
    //     if (!$accessToken) {
    //         return response()->json([
    //             'status' => 'error',
    //             'message' => 'Access token not received.',
    //             'auth_response' => $authResponse->json(),
    //         ], 401);
    //     }

    //     // Step 3: Build query parameters
    //     $queryParams = [
    //         'SubmissionId' => $submissionId
    //     ];

    //     // Optional RecordIds parameter (can be comma-separated list)
    //     if ($request->has('RecordIds') && !empty($request->get('RecordIds'))) {
    //         $queryParams['RecordIds'] = $request->get('RecordIds');
    //     }

    //     // Also check for 'Recordids' (lowercase 'i') as shown in documentation
    //     if ($request->has('Recordids') && !empty($request->get('Recordids'))) {
    //         $queryParams['Recordids'] = $request->get('Recordids');
    //     }

    //     // Step 4: Make DELETE request to TaxBandits API
    //     $endpoint = $apiUrl . '/Form941/Delete';

    //     // Build the full URL with query parameters
    //     $url = $endpoint . '?' . http_build_query($queryParams);

    //     try {
    //         $deleteResponse = Http::withHeaders([
    //             'Authorization' => 'Bearer ' . $accessToken,
    //             'Accept' => 'application/json'
    //         ])->delete($url);

    //         if ($deleteResponse->successful()) {
    //             $responseData = $deleteResponse->json();

    //             return response()->json([
    //                 'status' => 'success',
    //                 'message' => 'Form 941 deleted successfully.',
    //                 'data' => $responseData,
    //                 'query_params' => $queryParams, // For debugging
    //             ]);
    //         } else {
    //             $errorResponse = $deleteResponse->json();

    //             // Handle specific error cases
    //             $errorMessage = 'Failed to delete Form 941 from TaxBandits.';

    //             if (isset($errorResponse['StatusMessage'])) {
    //                 $errorMessage = $errorResponse['StatusMessage'];
    //             }

    //             // Check if it's a status-related error (Transmitted/Accepted can't be deleted in Live)
    //             if (str_contains($errorMessage, 'Transmitted') || str_contains($errorMessage, 'Accepted')) {
    //                 $errorMessage .= ' Note: In Live environment, Transmitted or Accepted returns cannot be deleted.';
    //             }

    //             return response()->json([
    //                 'status' => 'error',
    //                 'message' => $errorMessage,
    //                 'http_status' => $deleteResponse->status(),
    //                 'error_response' => $errorResponse,
    //                 'query_params' => $queryParams, // For debugging
    //             ], $deleteResponse->status());
    //         }
    //     } catch (\Exception $e) {
    //         return response()->json([
    //             'status' => 'error',
    //             'message' => 'Exception occurred while deleting Form 941: ' . $e->getMessage(),
    //         ], 500);
    //     }
    // }
    public function deleteForm941($id)
    {
        $filing = Filing::find($id);

        if (!$filing || !$filing->submission_id || !$filing->record_id) {
            return response()->json([
                'status' => 'error',
                'message' => 'Filing record not found or missing Submission ID.',
            ], 404);
        }

        $submissionId = "$filing->submission_id";
        $recordId = $filing->record_id;

        // TaxBandits Credentials
        $clientId = config('services.taxbandits.client_id');
        $clientSecret = config('services.taxbandits.client_secret');
        $userToken = config('services.taxbandits.user_token');
        $apiUrl = rtrim(config('services.taxbandits.api_url'), '/');

        // Generate JWT token
        $jwtToken = $this->generateTaxBanditsJWT($clientId, $clientSecret, $userToken);

        // Endpoint URL
        $endpoint = $apiUrl . "/Form941/delete?SubmissionId={$submissionId}";
        if (!empty($recordId)) {
            $endpoint .= "&Recordids={$recordId}";
        }

        // DELETE Request
        $response = Http::withHeaders([
            'Authorization' => "Bearer {$jwtToken}",
            'Content-Type'  => 'application/json',
        ])->delete($endpoint);

        $responseBody = $response->json();

        // Log the API call
        IrsFilingLog::create([
            'filing_id'     => $filing->id,
            'action'        => 'Delete Form941',
            'request_data'  => json_encode(['endpoint' => $endpoint]),
            'response_data' => json_encode($responseBody),
        ]);

        // Handle delete result
        if ($response->successful()) {
            // TaxBandits delete success → DB to delete
            $filing->delete();
            $status = 'success';
            $message = 'Form 941 return(s) deleted successfully from TaxBandits and local database.';
        } else {
            
            if (
                isset($responseBody['StatusCode']) && $responseBody['StatusCode'] == 404 &&
                isset($responseBody['Errors'][0]['Name']) && $responseBody['Errors'][0]['Name'] === 'SubmissionId'
            ) {
                $filing->delete();
                $status = 'success';
                $message = 'Form 941 return not found in TaxBandits, but deleted from local database.';
            } else {
                $status = 'error';
                $message = 'Failed to delete Form 941 return(s) from TaxBandits.';
            }
        }

        $apiResponse = [
            'status'      => $status,
            'message'     => $message,
            'http_status' => $response->status(),
            'api_response'=> $responseBody,
        ];

        return response()->json($apiResponse, $response->status());
    }


    public function downloadForm941Pdf($id)
    {
        $filing = Filing::find($id);

        if (!$filing || !$filing->submission_id || !$filing->record_id) {
            return response()->json([
                'status' => 'error',
                'message' => 'Filing record not found or missing Submission ID.',
            ], 404);
        }

        $submissionId = $filing->submission_id;
        $recordId = $filing->record_id;

        if (!$submissionId) {
            return response()->json([
                'status' => 'error',
                'message' => 'Submission ID is required.'
            ], 400);
        }

        $query = TaxbanditsPdfWebhook::where('submission_id', $submissionId);
        if ($recordId) {
            $query->where('record_id', $recordId);
        }

        $record = $query->first();

        if (!$record || empty($record->pdf_url)) {
            return response()->json([
                'status' => 'error',
                'message' => 'The PDF is not yet ready or available.'
            ], 404);
        }

        $pdfUrl = $record->pdf_url;

                return apiResponse([
                'status' => 'success',
                'pdfs' => $pdfUrl
            ], 200);
    }

    public function getForm941Status($id)
    {
        $filing = Filing::find($id);

        if (!$filing || !$filing->submission_id || !$filing->record_id) {
            return response()->json([
                'status' => 'error',
                'message' => 'Filing record not found or missing Submission ID.',
            ], 404);
        }

        $submissionId = $filing->submission_id;
        $recordId = $filing->record_id;

        $clientId = config('services.taxbandits.client_id');
        $clientSecret = config('services.taxbandits.client_secret');
        $userToken = config('services.taxbandits.user_token');
        $apiUrl = rtrim(config('services.taxbandits.api_url'), '/');

        $jwtToken = $this->generateTaxBanditsJWT($clientId, $clientSecret, $userToken);

        // Call Status API
        $endpoint = $apiUrl . "/Form941/status?SubmissionId={$submissionId}";
        if (!empty($recordId)) {
            $endpoint .= "&RecordIds={$recordId}";
        }

        $response = Http::withHeaders([
            'Authorization' => "Bearer {$jwtToken}",
            'Content-Type' => 'application/json',
        ])->get($endpoint);

        $data = $response->json();

        // Default status
        $statusCode = $data['StatusCode'] ?? null;
        $statusName = $data['Form941Records']['SuccessRecords'][0]['RecordStatus'] ?? null;
        $status_message = $data['StatusMessage'] ?? null;
        $isPending = in_array(strtolower($statusName), ['created', 'inprogress']);

        IrsFilingLog::create([
            'filing_id'     => $filing->id,
            'action'        => 'Check Form941 Status',
            'request_data'  => json_encode(['endpoint' => $endpoint]),
            'response_data' => json_encode($data),
        ]);

        return response()->json([
            'status' => $response->successful() ? 'success' : 'error',
            'status_code' => $statusCode,
            'status_name' => $statusName,
            'status_message'=> $status_message,
            'is_pending' => $isPending,
            'raw_data' => $data,
            'http_status' => $response->status(),
        ], $response->status());
//         $body = $response->json(); // API থেকে আসা ডাটা

// $successRecord = $body['Form941Records']['SuccessRecords'][0] ?? null;

// return response()->json([
//     'status'        => $response->successful() ? 'success' : 'error',
//     'status_code'   => $body['StatusCode'] ?? null,
//     'status_name'   => $body['StatusName'] ?? null,
//     'status_message'=> $body['StatusMessage'] ?? null,
//     'is_pending'    => ($successRecord['RecordStatus'] ?? '') === 'Under Process',
//     'submission_id' => $body['SubmissionId'] ?? null,
//     'record_status' => $successRecord['RecordStatus'] ?? null,
//     'business_id'   => $successRecord['BusinessId'] ?? null,
//     'payer_ref'     => $successRecord['PayerRef'] ?? null,
//     'record_id'     => $successRecord['RecordId'] ?? null,
//     'created_at'    => $successRecord['CreatedTs'] ?? null,
//     'updated_at'    => $successRecord['UpdatedTs'] ?? null,
//     'errors'        => $successRecord['Errors'] ?? ($body['Errors'] ?? []),
//     'http_status'   => $response->status(),
// ]);
    }

    public function getFiling(Request $request)
    {
        $filings = Filing::select('id', 'form_type', 'quarter', 'created_date', 'status', 'submission_id')
            ->where('form_type', 'Form941')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json([
            'status' => 'success',
            'data'   => $filings
        ]);
    }  
    
    public function viewFiling($id)
    {
        $filing = Filing::findOrFail($id);

        return response()->json(
            json_decode($filing->form_data, true) 
        );
    }


}
