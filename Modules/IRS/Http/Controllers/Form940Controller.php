<?php

namespace Modules\IRS\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Modules\Company\Entities\Company;
use Modules\User\Entities\UserDetails;
use Modules\Payslip\Http\Services\PayslipService;
use Modules\Payslip\Entities\Payslip;
use App\Helpers\XmlValidator;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Modules\IRS\Http\Traits\UserInformation;

class Form940Controller extends Controller
{
    use UserInformation;
    protected $payslipService;

    public function __construct(PayslipService $payslipService)
    {
        $this->payslipService = $payslipService;
    }

    public function generateForm940XML(Request $request)
    {
        $company = $this->getCompany($request->company_id);
        $employee = $this->getEmployeeDetails($request->employee_id);
        $futaSummary = $this->getFutaSummary($request->employee_id, $company->id, $request->from_date, $request->to_date);
        $employerContributions = $this->getEmployerContributionItems($request->employee_id, $request->from_date, $request->to_date);

        $xml = new \SimpleXMLElement('<Form940></Form940>');

        $xml->addChild('EIN', $company->employer_identification_number);
        $xml->addChild('BusinessName', $company->company_name);
        $xml->addChild('FilingYear', Carbon::parse($request->from_date)->year);
        $xml->addChild('TotalWagesPaid', $futaSummary['total_wages']);
        $xml->addChild('TaxableFUTAWages', $futaSummary['taxable_wages']);
        $xml->addChild('FUTARate', $futaSummary['futa_rate']);
        $xml->addChild('FUTATaxOwed', $futaSummary['futa_tax']);
        $xml->addChild('ReportingAgentPIN', $company->irs_efile_pin);

        $employerContributionsNode = $xml->addChild('EmployerContributions');
        foreach ($employerContributions as $contribution) {
            $itemNode = $employerContributionsNode->addChild('Contribution');
            $itemNode->addChild('Title', htmlspecialchars($contribution['title']));
            $itemNode->addChild('CompanyAmount', $contribution['company_amount']);
        }

        $fileName = 'irs/form940_' . Str::uuid() . '.xml';
        Storage::disk('local')->put($fileName, $xml->asXML());

        return response()->json([
            'status' => 'success',
            'message' => 'Form 940 XML generated successfully',
            'file_path' => $fileName,
            'xml_content' => $xml->asXML()
        ]);
    }

    public function validateXml(Request $request)
    {
        $fileName = $request->get('file_name');

        if (!$fileName) {
            return response()->json([
                'status' => 'error',
                'message' => 'No file_name parameter provided.'
            ], 400);
        }

        if (!Storage::exists($fileName)) {
            return response()->json([
                'status' => 'error',
                'message' => "XML file '{$fileName}' not found in storage."
            ], 404);
        }

        $xmlContent = Storage::get($fileName);
        $xsdPath = storage_path('app/xsd/IRS940.xsd');

        if (!file_exists($xsdPath)) {
            return response()->json([
                'status' => 'error',
                'message' => 'XSD schema file not found at path: ' . $xsdPath,
            ], 500);
        }

        $result = XmlValidator::validate($xmlContent, $xsdPath);

        if ($result['status']) {
            return response()->json([
                'status' => 'success',
                'message' => 'XML is valid according to IRS XSD schema.'
            ]);
        } else {
            return response()->json([
                'status' => 'fail',
                'message' => 'XML failed validation',
                'errors' => $result['errors']
            ], 422);
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

    private function getFutaSummary($employeeId, $companyId, $fromDate, $toDate)
    {
        $payslips = Payslip::where('employee_id', $employeeId)
            ->where('company_id', $companyId)
            ->whereBetween('first_date', [$fromDate, $toDate])
            ->get();

        $totalWages = 0;
        $taxableWages = 0;
        $futaLimit = 7000; // FUTA wage limit
        $futaRate = 0.006; // FUTA tax rate

        foreach ($payslips as $payslip) {
            $wages = $payslip->wages ?? 0;
            $totalWages += $wages;
            $taxableWages += min($wages, $futaLimit);
        }

        $futaTax = round($taxableWages * $futaRate, 2);

        return [
            'total_wages' => round($totalWages, 2),
            'taxable_wages' => round($taxableWages, 2),
            'futa_rate' => $futaRate,
            'futa_tax' => $futaTax,
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
                'company_amount' => $value->government_or_company_amount
            ];
        }

        return $response;
    }

    private function getEmployerContributionItems($employeeId, $fromDate, $toDate)
    {
        $payslips = Payslip::where('employee_id', $employeeId)
            ->whereBetween('first_date', [$fromDate, $toDate])
            ->get();

        $contributions = [];

        foreach ($payslips as $payslip) {
            $items = $this->getSocialDeduction($payslip);
            foreach ($items as $item) {
                // শুধুমাত্র FederalUnemploymentTax নামক আইটেম নেয়া হবে
                if (stripos($item['title'], 'FederalUnemploymentTax') === false) {
                    continue;
                }

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

        return array_values(array_map(function ($item) {
            return [
                'title' => $item['title'],
                'company_amount' => round($item['company_amount'], 2),
            ];
        }, $contributions));
    }

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
    private function getYearlyDateRange($year)
    {
        $startDate = Carbon::create($year, 1, 1)->startOfDay();
        $endDate   = Carbon::create($year, 12, 31)->endOfDay();

        return [$startDate, $endDate];
    }

    // public function submitForm940JsonToTaxBandits(Request $request)
    // { 
    //     $data = $request->all(); 

    //     $companyId = $data['company_id'] ?? null;
    //     $year = $data['Form941Records'][0]['ReturnHeader']['TaxYr'] ?? now()->year;
        
    //     //$quarter = $data['Form941Records'][0]['ReturnHeader']['Qtr'] ?? 'Q2';
    //     $company = $this->getCompany($companyId);
    //     if (!$company) {
    //         return response()->json([
    //             'status' => 'error',
    //             'message' => 'Company not found with the given ID.'
    //         ], 404);
    //     }

    //     [$startDate, $endDate] = $this->getYearlyDateRange($year);


    //     $salaryResource = new \Modules\IRS\Http\Resources\GetSalaryResource($company, $startDate, $endDate);
    //     $salaryData = $salaryResource->toArray(request());

    //     $grossPay        = $salaryData['gross_pay'] ?? 0;
    //     $incomeTax       = $salaryData['income_tax'] ?? 0;

    //     // Government deductions
    //     $governmentDeductions = $salaryData['government_deductions_yearly'] ?? [];
    //     $socialSecurityTaxCon = floatval($governmentDeductions['Government Deductions 2'] ?? 0);
       

    //     $totalEmployees  = $salaryData['total_employee'] ?? 0;
    //     $companyName     = $salaryData['company_name'] ?? $company->name;
    //     $ein             = $salaryData['employer_identification_number'] ?? $company->employer_identification_number;
    //     $companyEmail     = $salaryData['company_email'] ?? $company->company_email;
    //     $companyPhone     = $salaryData['company_phone'] ?? $company->company_phone;
    

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

    //     // Step 3: Prepare tax values with safety checks and 2024 credit reduction rates
    //     $form940Record = $data['Form940Records'][0] ?? [];
    //     $returnData = $form940Record['ReturnData']['Form940'] ?? [];
    //     $scheduleA = $form940Record['ReturnData']['ScheduleA'] ?? [];

    //     // Updated 2024 Credit Reduction Rates
    //     $creditReductionRates = [
    //         'CA' => 0.009, // California 0.9%
    //         'NY' => 0.009, // New York 0.9%
    //         'VI' => 0.042  // Virgin Islands 4.2%
    //     ];

    //     // Process Schedule A with updated rates and calculate total credit reduction
    //     $updatedScheduleA = [];
    //     $totalCreditReduction = 0;

    //     foreach ($scheduleA as $schedule) {
    //         $stateCd = $schedule['StateCd'] ?? '';
    //         $taxableWages = floatval($schedule['TotTaxableFUTAwagesAmt'] ?? 0);
            
    //         // Use updated rates if state has credit reduction
    //         if (isset($creditReductionRates[$stateCd])) {
    //             $creditReductionRate = $creditReductionRates[$stateCd];
    //             $creditReductionAmt = round($taxableWages * $creditReductionRate, 2);
    //         } else {
    //             $creditReductionRate = floatval($schedule['CreditReductionRt'] ?? 0);
    //             $creditReductionAmt = floatval($schedule['CreditReductionAmt'] ?? 0);
    //         }

    //         $updatedScheduleA[] = [
    //             'StateCd' => $stateCd,
    //             'TotTaxableFUTAwagesAmt' => $taxableWages,
    //             'CreditReductionRt' => $creditReductionRate,
    //             'CreditReductionAmt' => $creditReductionAmt
    //         ];

    //         $totalCreditReduction += $creditReductionAmt;
    //     }

    //     // FUTA tax calculations following TaxBandits documentation
    //     $totTaxableWagesAmt = floatval($grossPay  ?? 0);
    //     $futaTaxBeforeAdjAmt = floatval($totTaxableWagesAmt * 0.006) ?? 0;
    //     $maxCreditAmt = floatval($returnData['MaxCreditAmt'] ?? 0);
    //     $futaAdjAmt = floatval($returnData['FUTAAdjAmt'] ?? 0);

        
    //     // Use calculated credit reduction if not provided
    //     $totCrdtRedAmt = floatval($returnData['TotCrdtRedAmt'] ?? $totalCreditReduction);

    //     // FUTA Tax After Adjustments = Line 8 + Line 9 + Line 10 + Line 11 (per documentation)
    //     $futaTaxAfterAdjAmt = floatval($returnData['FUTATaxAfterAdjAmt'] ?? 
    //         round($futaTaxBeforeAdjAmt + $maxCreditAmt + $futaAdjAmt + $totCrdtRedAmt, 2));

    //     // Quarterly tax liabilities with $500 threshold logic
    //     $quarterlyTaxes = [
    //         'FirstQtrTaxLiabilityAmt' => floatval($returnData['FirstQtrTaxLiabilityAmt'] ?? 0),
    //         'secondQtrTaxLiabilityAmt' => floatval($returnData['secondQtrTaxLiabilityAmt'] ?? 0),
    //         'ThirdQtrTaxLiabilityAmt' => floatval($returnData['ThirdQtrTaxLiabilityAmt'] ?? 0),
    //         'FourthQtrTaxLiabilityAmt' => floatval($returnData['FourthQtrTaxLiabilityAmt'] ?? 0),
    //     ];

    //     // Apply $500 threshold rule if no quarterly amounts provided
    //     if (array_sum($quarterlyTaxes) === 0.0) {
    //         $perQuarterRaw = $futaTaxAfterAdjAmt / 4;
    //         $carryForward = 0.0;
    //         $adjustedQuarters = [];

    //         for ($i = 1; $i <= 4; $i++) {
    //             $currentLiability = $perQuarterRaw + $carryForward;
    //             if ($currentLiability >= 500) {
    //                 $adjustedQuarters[$i] = round($currentLiability, 2);
    //                 $carryForward = 0;
    //             } else {
    //                 $adjustedQuarters[$i] = 0;
    //                 $carryForward = $currentLiability;
    //             }
    //         }

    //         // Add any remaining carryforward to the last quarter
    //         if ($carryForward > 0) {
    //             $adjustedQuarters[4] += round($carryForward, 2);
    //         }

    //         $quarterlyTaxes = [
    //             'FirstQtrTaxLiabilityAmt' => $adjustedQuarters[1] ?? 0,
    //             'secondQtrTaxLiabilityAmt' => $adjustedQuarters[2] ?? 0,
    //             'ThirdQtrTaxLiabilityAmt' => $adjustedQuarters[3] ?? 0,
    //             'FourthQtrTaxLiabilityAmt' => $adjustedQuarters[4] ?? 0,
    //         ];
    //     }

    //     $totTaxLiabilityAmt = floatval($returnData['TotTaxLiabilityAmt'] ?? array_sum($quarterlyTaxes));
    //     $totDepositAmt = floatval($returnData['TotDepositAmt'] ?? 0);
    //     $balanceDueAmt = floatval($returnData['BalanceDueAmt'] ?? max(0, $totTaxLiabilityAmt - $totDepositAmt));
    //     $overPaidAmt = floatval($returnData['OverPaidAmt'] ?? max(0, $totDepositAmt - $totTaxLiabilityAmt));

    //     // Build JSON payload for TaxBandits API
    //     $requestPayload = [
    //         "Form940Records" => [
    //             [
    //                 "Sequence" => $form940Record['Sequence'] ?? null,
    //                 "ReturnHeader" => [
    //                     "ReturnType" => $form940Record['ReturnHeader']['ReturnType'] ?? 'FORM940',
    //                     "TaxYr" => $form940Record['ReturnHeader']['TaxYr'] ?? '2024',
    //                     "Business" => [
    //                         "BusinessId" => $form940Record['ReturnHeader']['Business']['BusinessId'] ?? null,
    //                         "BusinessNm" => $form940Record['ReturnHeader']['Business']['BusinessNm'] ?? $company->company_name,
    //                         "TradeNm" => $form940Record['ReturnHeader']['Business']['TradeNm'] ?? $company->trade_name,
    //                         "PayerRef" => $form940Record['ReturnHeader']['Business']['PayerRef'] ?? 'COMP' . $company->id,
    //                         "IsEIN" => true,
    //                         "EINorSSN" => $form940Record['ReturnHeader']['Business']['EINorSSN'] ?? $company->employer_identification_number,
    //                         "Email" => $form940Record['ReturnHeader']['Business']['Email'] ?? $company->email,
    //                         "ContactNm" => $form940Record['ReturnHeader']['Business']['ContactNm'] ?? $company->contact_person,
    //                         "Phone" => $form940Record['ReturnHeader']['Business']['Phone'] ?? $company->phone,
    //                         "PhoneExtn" => $form940Record['ReturnHeader']['Business']['PhoneExtn'] ?? null,
    //                         "Fax" => $form940Record['ReturnHeader']['Business']['Fax'] ?? $company->fax,
    //                         "BusinessType" => $form940Record['ReturnHeader']['Business']['BusinessType'] ?? "CORP",
    //                         "SigningAuthority" => [
    //                             "Name" => $form940Record['ReturnHeader']['Business']['SigningAuthority']['Name'] ?? $company->contact_person,
    //                             "Phone" => $form940Record['ReturnHeader']['Business']['SigningAuthority']['Phone'] ?? preg_replace('/[^0-9]/', '', $company->phone),
    //                             "BusinessMemberType" => $form940Record['ReturnHeader']['Business']['SigningAuthority']['BusinessMemberType'] ?? "TAXOFFICER"
    //                         ],
    //                         "KindOfEmployer" => $form940Record['ReturnHeader']['Business']['KindOfEmployer'] ?? null,
    //                         "KindOfPayer" => $form940Record['ReturnHeader']['Business']['KindOfPayer'] ?? null,
    //                         "IsBusinessTerminated" => $form940Record['ReturnHeader']['Business']['IsBusinessTerminated'] ?? false,
    //                         "IsForeign" => $form940Record['ReturnHeader']['Business']['IsForeign'] ?? false,
    //                         "USAddress" => [
    //                             "Address1" => $form940Record['ReturnHeader']['Business']['USAddress']['Address1'] ?? $company->address_line_1,
    //                             "Address2" => $form940Record['ReturnHeader']['Business']['USAddress']['Address2'] ?? $company->address_line_2,
    //                             "City" => $form940Record['ReturnHeader']['Business']['USAddress']['City'] ?? $company->city,
    //                             "State" => $form940Record['ReturnHeader']['Business']['USAddress']['State'] ?? $company->state,
    //                             "ZipCd" => $form940Record['ReturnHeader']['Business']['USAddress']['ZipCd'] ?? $company->zip_code
    //                         ],
    //                         "ForeignAddress" => $form940Record['ReturnHeader']['Business']['ForeignAddress'] ?? [
    //                             "Address1" => null,
    //                             "Address2" => null,
    //                             "City" => null,
    //                             "ProvinceOrStateNm" => null,
    //                             "Country" => null,
    //                             "PostalCd" => null
    //                         ]
    //                     ],
    //                     "IsThirdPartyDesignee" => $form940Record['ReturnHeader']['IsThirdPartyDesignee'] ?? true,
    //                     "ThirdPartyDesignee" => $form940Record['ReturnHeader']['ThirdPartyDesignee'] ?? [
    //                         "Name" => "Third Party Name",
    //                         "Phone" => "1234567890",
    //                         "PIN" => "12345"
    //                     ],
    //                     "SignatureDetails" => $form940Record['ReturnHeader']['SignatureDetails'] ?? [
    //                         "SignatureType" => "ONLINE_SIGN_PIN",
    //                         "OnlineSignaturePIN" => [
    //                             "PIN" => $company->irs_efile_pin ?? "123456"
    //                         ],
    //                         "ReportingAgentPIN" => [
    //                             "PIN" => null
    //                         ],
    //                         "Form8453EMP" => null
    //                     ]
    //                 ],
    //                 "ReturnData" => [
    //                     "Form940" => [
    //                         "OneStateCd" => $returnData['OneStateCd'] ?? null,
    //                         "IsCreditReduction" => $returnData['IsCreditReduction'] ?? (count($updatedScheduleA) > 0),
    //                         "IsSuccessorEmployer" => $returnData['IsSuccessorEmployer'] ?? false,
    //                         "IsPymtsMadeToEmployees" => $returnData['IsPymtsMadeToEmployees'] ?? true,
    //                         "IsBusinessClosed" => $returnData['IsBusinessClosed'] ?? false,
    //                         "IsMultiState" => $returnData['IsMultiState'] ?? (count($updatedScheduleA) > 1),
    //                         "WagesAmt" => round(floatval($returnData['WagesAmt'] ?? 0), 2),
    //                         "ExemptWagesAmt" => round(floatval($returnData['ExemptWagesAmt'] ?? 0), 2),
    //                         "IsFringeBenfs" => $returnData['IsFringeBenfs'] ?? false,
    //                         "IsGrpTermLifeIns" => $returnData['IsGrpTermLifeIns'] ?? false,
    //                         "IsRetrmntOrPension" => $returnData['IsRetrmntOrPension'] ?? false,
    //                         "IsDepCare" => $returnData['IsDepCare'] ?? false,
    //                         "IsOtherExempt" => $returnData['IsOtherExempt'] ?? false,
    //                         "WagesOverLmtAmt" => round(floatval($returnData['WagesOverLmtAmt'] ?? 0), 2),
    //                         "TotExemptWagesAmt" => round(floatval($returnData['TotExemptWagesAmt'] ?? 0), 2),
    //                         "TotTaxableWagesAmt" => round($totTaxableWagesAmt, 2),
    //                         "FUTATaxBeforeAdjAmt" => round($futaTaxBeforeAdjAmt, 2),
    //                         "MaxCreditAmt" => round($maxCreditAmt, 2),
    //                         "FUTAAdjAmt" => round($futaAdjAmt, 2),
    //                         "TotCrdtRedAmt" => round($totCrdtRedAmt, 2),
    //                         "FUTATaxAfterAdjAmt" => round($futaTaxAfterAdjAmt, 2),
    //                         "TotDepositAmt" => round($totDepositAmt, 2),
    //                         "FirstQtrTaxLiabilityAmt" => round($quarterlyTaxes['FirstQtrTaxLiabilityAmt'], 2),
    //                         "secondQtrTaxLiabilityAmt" => round($quarterlyTaxes['secondQtrTaxLiabilityAmt'], 2),
    //                         "ThirdQtrTaxLiabilityAmt" => round($quarterlyTaxes['ThirdQtrTaxLiabilityAmt'], 2),
    //                         "FourthQtrTaxLiabilityAmt" => round($quarterlyTaxes['FourthQtrTaxLiabilityAmt'], 2),
    //                         "TotTaxLiabilityAmt" => round($totTaxLiabilityAmt, 2),
    //                         "BalanceDueAmt" => round($balanceDueAmt, 2),
    //                         "OverPaidAmt" => round($overPaidAmt, 2),
    //                         "OverPaymentRecoveryType" => $returnData['OverPaymentRecoveryType'] ?? null
    //                     ],
    //                     "IRSPaymentType" => $form940Record['ReturnData']['IRSPaymentType'] ?? "EFTPS",
    //                     "IRSPayment" => $form940Record['ReturnData']['IRSPayment'] ?? [
    //                         "BankRoutingNum" => null,
    //                         "AccountType" => null,
    //                         "BankAccountNum" => null,
    //                         "Phone" => null
    //                     ],
    //                     "FinalPayRoll" => $form940Record['ReturnData']['FinalPayRoll'] ?? [
    //                         "PersonNm" => null,
    //                         "IsForeign" => false,
    //                         "USAddress" => [
    //                             "Address1" => null,
    //                             "Address2" => null,
    //                             "City" => null,
    //                             "State" => null,
    //                             "ZipCd" => null
    //                         ],
    //                         "ForeignAddress" => [
    //                             "Address1" => null,
    //                             "Address2" => null,
    //                             "City" => null,
    //                             "ProvinceOrStateNm" => null,
    //                             "Country" => null,
    //                             "PostalCd" => null
    //                         ]
    //                     ],
    //                     "ScheduleA" => $updatedScheduleA
    //                 ]
    //             ]
    //         ]
    //     ];

    //     // Step 5: Submit JSON payload to TaxBandits API
    //     $endpoint = $apiUrl . '/Form940/Create';

    //     $transmitResponse = Http::withHeaders([
    //         'Authorization' => 'Bearer ' . $accessToken,
    //         'Content-Type' => 'application/json',
    //         'Accept' => 'application/json'
    //     ])->post($endpoint, $requestPayload);

    //     if ($transmitResponse->successful()) {
    //         return response()->json([
    //             'status' => 'success',
    //             'message' => 'Form 940 submitted successfully to TaxBandits',
    //             'submission_id' => $transmitResponse->json()['Form940Records'][0]['SubmissionId'] ?? null,
    //             'response' => $transmitResponse->json()
    //         ]);
    //     } else {
    //         $responseJson = $transmitResponse->json();

    //         // Duplicate Return Error Handle
    //         if (isset($responseJson['Errors']) && is_array($responseJson['Errors'])) {
    //             foreach ($responseJson['Errors'] as $error) {
    //                 if (isset($error['Id']) && $error['Id'] === 'F23-100213') {
    //                     return response()->json([
    //                         'status' => 'error',
    //                         'message' => 'Duplicate return error: A return has already been filed for the same EIN and Year.',
    //                         'error_details' => $error['Message'] ?? 'Duplicate return',
    //                         'http_status' => 400
    //                     ], 400);
    //                 }
    //             }
    //         }

    //         // Other Errors
    //         return response()->json([
    //             'status' => 'error',
    //             'message' => $responseJson['Message'] ?? 'Failed to submit Form 940 to TaxBandits',
    //             'error' => $responseJson['Message'] ?? 'Unknown error',
    //             'http_status' => $transmitResponse->status(),
    //             'raw_body' => $transmitResponse->body()
    //         ], 400);
    //     }
    // }

    public function submitForm940JsonToTaxBandits(Request $request)
{
    $data = $request->all();
    $companyId = $data['company_id'] ?? null;
    $record = $data['Form940Records'][0] ?? [];
    $year = $record['ReturnHeader']['TaxYr'] ?? now()->year;

    // Company check
    $company = $this->getCompany($companyId);
    if (!$company) {
        return response()->json(['status' => 'error', 'message' => 'Company not found'], 404);
    }

    // Payroll data
    [$startDate, $endDate] = $this->getYearlyDateRange($year);
    $salaryResource = new \Modules\IRS\Http\Resources\GetSalaryResource($company, $startDate, $endDate);
    $salaryData = $salaryResource->toArray(request());

    $grossPay = floatval($salaryData['gross_pay'] ?? 0);
    $exemptWages = floatval($salaryData['exempt_wages'] ?? 0);
    $stateWages = $salaryData['state_wages'] ?? [];
    $employeeWages = $salaryData['employee_wages'] ?? [];

    // Basic validation
    if ($grossPay <= 0) {
        return response()->json(['status' => 'error', 'message' => 'No wages found for the specified year'], 400);
    }

    // Calculate Line 5: Excess wages per employee over $7,000
    $excessWagesPerEmployee = 0;
    foreach ($employeeWages as $empWage) {
        if ($empWage > 7000) {
            $excessWagesPerEmployee += ($empWage - 7000);
        }
    }

    // Line 6: Subtotal (Line 4 + Line 5)
    $subtotal = $exemptWages + $excessWagesPerEmployee;

    // Line 7: Total taxable FUTA wages (Line 3 - Line 6)
    $totTaxableWages = max(0, $grossPay - $subtotal);

    // Line 8: FUTA tax before adjustments (Line 7 × 0.006)
    $futaTaxBeforeAdj = round($totTaxableWages * 0.006, 2);

    // Credit Reduction States and rates
    $creditReductionRates = [
        'CA' => 0.009, // California
        'NY' => 0.009, // New York  
        'VI' => 0.042  // Virgin Islands
    ];

    // Schedule A calculations
    $scheduleA = [];
    $totalCreditReduction = 0;

    foreach ($creditReductionRates as $state => $rate) {
        $wages = floatval($stateWages[$state] ?? 0);
        if ($wages > 0) {
            $creditReductionAmt = round($wages * $rate, 2);
            $scheduleA[] = [
                'StateCd' => $state,
                'TotTaxableFUTAwagesAmt' => $wages,
                'CreditReductionRt' => $rate,
                'CreditReductionAmt' => $creditReductionAmt
            ];
            $totalCreditReduction += $creditReductionAmt;
        }
    }

    // Check for state unemployment tax exclusions (Line 9-10 adjustments)
    $stateUnemploymentExclusion = 0;
    $excludedFromStateUI = $salaryData['excluded_from_state_ui'] ?? 0;
    if ($excludedFromStateUI > 0) {
        // Line 9: If ALL taxable FUTA wages were excluded from state unemployment tax
        if ($excludedFromStateUI >= $totTaxableWages) {
            $stateUnemploymentExclusion = round($totTaxableWages * 0.054, 2);
        } else {
            // Line 10: If SOME wages were excluded (partial exclusion)
            $stateUnemploymentExclusion = round($excludedFromStateUI * 0.054, 2);
        }
    }

    // Line 12: Total FUTA tax after adjustments
    $futaTaxAfterAdj = round($futaTaxBeforeAdj + $stateUnemploymentExclusion + $totalCreditReduction, 2);

    // Quarterly liability calculation (improved logic)
    $quarterly = [1 => 0, 2 => 0, 3 => 0, 4 => 0];
    
    if ($futaTaxAfterAdj > 500) {
        // Distribute liability across quarters
        $perQuarter = $futaTaxAfterAdj / 4;
        $cumulativeBalance = 0;
        
        for ($i = 1; $i <= 4; $i++) {
            $cumulativeBalance += $perQuarter;
            
            // If cumulative balance exceeds $500, it becomes liability
            if ($cumulativeBalance > 500) {
                $quarterly[$i] = round($cumulativeBalance, 2);
                $cumulativeBalance = 0;
            }
        }
        
        // If there's remaining balance, add to Q4
        if ($cumulativeBalance > 0) {
            $quarterly[4] += round($cumulativeBalance, 2);
        }
    } else {
        // If total tax is $500 or less, all goes to Q4
        $quarterly[4] = $futaTaxAfterAdj;
    }

    $totTaxLiability = array_sum($quarterly);
    $totDeposit = floatval($salaryData['futa_deposits'] ?? 0);
    $balanceDue = max(0, $totTaxLiability - $totDeposit);
    $overPaid = max(0, $totDeposit - $totTaxLiability);

    // TaxBandits credentials
    $clientId = config('services.taxbandits.client_id');
    $clientSecret = config('services.taxbandits.client_secret');
    $userToken = config('services.taxbandits.user_token');
    $authUrl = config('services.taxbandits.auth_url');
    $apiUrl = config('services.taxbandits.api_url');

    if (empty($clientId) || empty($clientSecret) || empty($userToken)) {
        return response()->json(['status' => 'error', 'message' => 'Missing TaxBandits credentials'], 400);
    }

    try {
        $jwtToken = $this->generateTaxBanditsJWT($clientId, $clientSecret, $userToken);
    } catch (\Exception $e) {
        return response()->json(['status' => 'error', 'message' => 'JWT generation failed: ' . $e->getMessage()], 500);
    }

    // Get Access Token
    $authResponse = Http::withHeaders(['Authentication' => $jwtToken])->get($authUrl);
    if ($authResponse->failed()) {
        return response()->json([
            'status' => 'error', 
            'message' => 'Authentication failed', 
            'details' => $authResponse->json()
        ], 401);
    }
    
    $accessToken = $authResponse->json()['AccessToken'] ?? null;
    if (!$accessToken) {
        return response()->json(['status' => 'error', 'message' => 'Access token missing'], 401);
    }

    // Build API payload
    $payload = [
        "Form940Records" => [
            [
                "Sequence" => null,
                "ReturnHeader" => [
                    "ReturnType" => "FORM940",
                    "TaxYr" => (string)$year,
                    "Business" => [
                        "BusinessId" => null,
                        "BusinessNm" => $company->name,
                        "PayerRef" => "COMP" . $company->id,
                        "IsEIN" => true,
                        "EINorSSN" => $company->employer_identification_number,
                        "Email" => $company->company_email,
                        "Phone" => $company->company_phone,
                        // "USAddress" => [
                        //     "Address1" => $company->address1,
                        //     "City" => $company->city,
                        //     "State" => $company->state,
                        //     "ZipCd" => $company->zip
                        // ]
                        'IsForeign' => $record['ReturnHeader']['IsForeign'] ?? false,
                        'USAddress' => $record['ReturnHeader']['Business']['USAddress'] ?? null,
                        'ForeignAddress' => $record['ReturnHeader']['Business']['ForeignAddress'] ?? null,
                    ],
                    "IsThirdPartyDesignee" => true,
                    "ThirdPartyDesignee" => [
                        "Name" => $record['ReturnHeader']['ThirdPartyDesignee']['Name'] ?? 'Devok Phils',
                        "Phone" => $record['ReturnHeader']['ThirdPartyDesignee']['Phone'] ?? '9421768534',
                        "PIN" => $record['ReturnHeader']['ThirdPartyDesignee']['PIN'] ?? '96512'
                    ],
                    "SignatureDetails" => [
                        "SignatureType" => "ONLINE_SIGN_PIN",
                        "OnlineSignaturePIN" => ["PIN" => $company->irs_efile_pin ?? '123456']
                    ]
                ],
                "ReturnData" => [
                    "Form940" => [
                        "IsCreditReduction" => count($scheduleA) > 0,
                        "IsPymtsMadeToEmployees" => true,
                        "WagesAmt" => round($grossPay, 2),
                        "ExemptWagesAmt" => round($exemptWages, 2),
                        "ExcessWagesAmt" => round($excessWagesPerEmployee, 2),
                        "TotTaxableWagesAmt" => round($totTaxableWages, 2),
                        "FUTATaxBeforeAdjAmt" => round($futaTaxBeforeAdj, 2),
                        "StateUITaxExclusionAmt" => round($stateUnemploymentExclusion, 2),
                        "TotCrdtRedAmt" => round($totalCreditReduction, 2),
                        "FUTATaxAfterAdjAmt" => round($futaTaxAfterAdj, 2),
                        "FirstQtrTaxLiabilityAmt" => round($quarterly[1], 2),
                        "SecondQtrTaxLiabilityAmt" => round($quarterly[2], 2), // Fixed capitalization
                        "ThirdQtrTaxLiabilityAmt" => round($quarterly[3], 2),
                        "FourthQtrTaxLiabilityAmt" => round($quarterly[4], 2),
                        "TotTaxLiabilityAmt" => round($totTaxLiability, 2),
                        "TotFUTADepositAmt" => round($totDeposit, 2),
                        "BalanceDueAmt" => round($balanceDue, 2),
                        "OverPaidAmt" => round($overPaid, 2)
                    ],
                    "ScheduleA" => $scheduleA,
                    "IRSPaymentType" => $balanceDue > 0 ? "EFTPS" : null,
                    "IRSPayment" => $balanceDue > 0 ? [
                        "BankRoutingNum" => $company->bank_routing_number ?? null,
                        "AccountType" => $company->bank_account_type ?? "CHECKING",
                        "BankAccountNum" => $company->bank_account_number ?? null,
                        "Phone" => $company->company_phone
                    ] : null
                ]
            ]
        ]
    ];
return $payload;
    // Debug payload (remove in production)
    \Log::info('Form 940 Payload:', $payload);

    // Submit to TaxBandits
    $endpoint = $apiUrl . '/Form940/Create';
    $response = Http::withHeaders([
        'Authorization' => 'Bearer ' . $accessToken,
        'Content-Type' => 'application/json'
    ])->timeout(60)->post($endpoint, $payload);

    if ($response->successful()) {
        $responseData = $response->json();
        return response()->json([
            'status' => 'success',
            'message' => 'Form 940 submitted successfully',
            'submission_id' => $responseData['Form940Records'][0]['SubmissionId'] ?? null,
            'calculations' => [
                'gross_pay' => $grossPay,
                'exempt_wages' => $exemptWages,
                'excess_wages_per_employee' => $excessWagesPerEmployee,
                'taxable_futa_wages' => $totTaxableWages,
                'futa_tax_before_adj' => $futaTaxBeforeAdj,
                'credit_reduction' => $totalCreditReduction,
                'futa_tax_after_adj' => $futaTaxAfterAdj,
                'quarterly_liability' => $quarterly,
                'balance_due' => $balanceDue,
                'overpaid' => $overPaid
            ],
            'response' => $responseData
        ]);
    } else {
        $errorData = $response->json();
        \Log::error('Form 940 Submission Failed:', [
            'status' => $response->status(),
            'response' => $errorData,
            'payload' => $payload
        ]);

        return response()->json([
            'status' => 'error',
            'message' => $errorData['Message'] ?? 'Failed to submit Form 940',
            'http_status' => $response->status(),
            'errors' => $errorData['Errors'] ?? [],
            'raw_body' => $response->body()
        ], 400);
    }
}


public function updateForm940(Request $request)
{
    $data = $request->all(); // JSON body

    // TaxBandits Update API requires SubmissionId and RecordId in the main request body
    $submissionId = $data['Submsubmission_idissionId'] ?? $data['submission_id'] ?? null;
    //$companyId = $data['company_id'] ?? null;
    
    // Get RecordId from Form940Records array as per TaxBandits documentation
    $form940Record = $data['Form940Records'][0] ?? [];
    $recordId = $form940Record['record_id'] ?? $data['record_id'] ?? null;
    $companyId = $form940Record['company_id'] ?? $data['company_id'] ?? null;

    // Validate required parameters
    if (!$submissionId || !$recordId) {
        return response()->json([
            'status' => 'error',
            'message' => 'SubmissionId and RecordId are required for update. Please provide SubmissionId in root and RecordId in Form940Records array.'
        ], 400);
    }

    $company = $this->getCompany($companyId);
    if (!$company) {
        return response()->json([
            'status' => 'error',
            'message' => 'Company not found with the given ID.'
        ], 404);
    }

    // Load credentials
    $clientId = config('services.taxbandits.client_id');
    $clientSecret = config('services.taxbandits.client_secret');
    $userToken = config('services.taxbandits.user_token');
    $authUrl = config('services.taxbandits.auth_url');
    $apiUrl = config('services.taxbandits.api_url');

    if (empty($userToken) || empty($clientId) || empty($clientSecret)) {
        return response()->json([
            'status' => 'error',
            'message' => 'Missing TaxBandits credentials.',
        ], 400);
    }

    // Step 1: Generate JWT token
    try {
        $jwtToken = $this->generateTaxBanditsJWT($clientId, $clientSecret, $userToken);
    } catch (\Exception $e) {
        return response()->json([
            'status' => 'error',
            'message' => 'Failed to generate JWT token: ' . $e->getMessage(),
        ], 500);
    }

    // Step 2: Get Access Token
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

    // Step 3: Prepare tax values with 2024 credit reduction rates
    $returnData = $form940Record['ReturnData']['Form940'] ?? [];
    $scheduleA = $form940Record['ReturnData']['ScheduleA'] ?? [];

    // Updated 2024 Credit Reduction Rates as per TaxBandits documentation
    $creditReductionRates = [
        'CA' => 0.009, // California 0.9%
        'NY' => 0.009, // New York 0.9%
        'VI' => 0.042  // Virgin Islands 4.2%
    ];

    // Process Schedule A with updated rates and calculate total credit reduction
    $updatedScheduleA = [];
    $totalCreditReduction = 0;

    foreach ($scheduleA as $schedule) {
        $stateCd = $schedule['StateCd'] ?? '';
        $taxableWages = floatval($schedule['TotTaxableFUTAwagesAmt'] ?? 0);
        
        // Use updated rates if state has credit reduction for 2024
        if (isset($creditReductionRates[$stateCd])) {
            $creditReductionRate = $creditReductionRates[$stateCd];
            $creditReductionAmt = round($taxableWages * $creditReductionRate, 2);
        } else {
            $creditReductionRate = floatval($schedule['CreditReductionRt'] ?? 0);
            $creditReductionAmt = floatval($schedule['CreditReductionAmt'] ?? 0);
        }

        $updatedScheduleA[] = [
            'StateCd' => $stateCd,
            'TotTaxableFUTAwagesAmt' => round($taxableWages, 2),
            'CreditReductionRt' => $creditReductionRate,
            'CreditReductionAmt' => round($creditReductionAmt, 2)
        ];

        $totalCreditReduction += $creditReductionAmt;
    }

    // FUTA tax calculations following TaxBandits documentation
    $totTaxableWagesAmt = floatval($returnData['TotTaxableWagesAmt'] ?? 0);
    $futaTaxBeforeAdjAmt = floatval($returnData['FUTATaxBeforeAdjAmt'] ?? round($totTaxableWagesAmt * 0.006, 2));
    $maxCreditAmt = floatval($returnData['MaxCreditAmt'] ?? 0);
    $futaAdjAmt = floatval($returnData['FUTAAdjAmt'] ?? 0);
    
    // Use calculated credit reduction if not provided
    $totCrdtRedAmt = floatval($returnData['TotCrdtRedAmt'] ?? $totalCreditReduction);

    // FUTA Tax After Adjustments = Line 8 + Line 9 + Line 10 + Line 11 (per documentation)
    $futaTaxAfterAdjAmt = floatval($returnData['FUTATaxAfterAdjAmt'] ?? 
        round($futaTaxBeforeAdjAmt + $maxCreditAmt + $futaAdjAmt + $totCrdtRedAmt, 2));

    // Quarterly tax liabilities with $500 threshold logic
    $quarterlyTaxes = [
        'FirstQtrTaxLiabilityAmt' => floatval($returnData['FirstQtrTaxLiabilityAmt'] ?? 0),
        'secondQtrTaxLiabilityAmt' => floatval($returnData['secondQtrTaxLiabilityAmt'] ?? 0),
        'ThirdQtrTaxLiabilityAmt' => floatval($returnData['ThirdQtrTaxLiabilityAmt'] ?? 0),
        'FourthQtrTaxLiabilityAmt' => floatval($returnData['FourthQtrTaxLiabilityAmt'] ?? 0),
    ];

    // Apply $500 threshold rule if no quarterly amounts provided
    if (array_sum($quarterlyTaxes) === 0.0 && $futaTaxAfterAdjAmt > 0) {
        $perQuarterRaw = $futaTaxAfterAdjAmt / 4;
        $carryForward = 0.0;
        $adjustedQuarters = [];

        for ($i = 1; $i <= 4; $i++) {
            $currentLiability = $perQuarterRaw + $carryForward;
            if ($currentLiability >= 500) {
                $adjustedQuarters[$i] = round($currentLiability, 2);
                $carryForward = 0;
            } else {
                $adjustedQuarters[$i] = 0;
                $carryForward = $currentLiability;
            }
        }

        // Add any remaining carryforward to the last quarter
        if ($carryForward > 0) {
            $adjustedQuarters[4] = round(($adjustedQuarters[4] ?? 0) + $carryForward, 2);
        }

        $quarterlyTaxes = [
            'FirstQtrTaxLiabilityAmt' => $adjustedQuarters[1] ?? 0,
            'secondQtrTaxLiabilityAmt' => $adjustedQuarters[2] ?? 0,
            'ThirdQtrTaxLiabilityAmt' => $adjustedQuarters[3] ?? 0,
            'FourthQtrTaxLiabilityAmt' => $adjustedQuarters[4] ?? 0,
        ];
    }

    $totTaxLiabilityAmt = floatval($returnData['TotTaxLiabilityAmt'] ?? array_sum($quarterlyTaxes));
    $totDepositAmt = floatval($returnData['TotDepositAmt'] ?? 0);
    $balanceDueAmt = floatval($returnData['BalanceDueAmt'] ?? max(0, $totTaxLiabilityAmt - $totDepositAmt));
    $overPaidAmt = floatval($returnData['OverPaidAmt'] ?? max(0, $totDepositAmt - $totTaxLiabilityAmt));

    // Build JSON payload for TaxBandits Update API following exact documentation structure
    $requestPayload = [
        "SubmissionId" => $submissionId,
        "Form940Records" => [
            [
                "RecordId" => $recordId,
                "Sequence" => $form940Record['Sequence'] ?? null,
                "ReturnHeader" => [
                    "ReturnType" => $form940Record['ReturnHeader']['ReturnType'] ?? 'FORM940',
                    "TaxYr" => $form940Record['ReturnHeader']['TaxYr'] ?? '2024',
                    "Business" => [
                        "BusinessId" => $form940Record['ReturnHeader']['Business']['BusinessId'] ?? null,
                        "BusinessNm" => $form940Record['ReturnHeader']['Business']['BusinessNm'] ?? $company->company_name,
                        "TradeNm" => $form940Record['ReturnHeader']['Business']['TradeNm'] ?? $company->trade_name,
                        "PayerRef" => $form940Record['ReturnHeader']['Business']['PayerRef'] ?? 'COMP' . $company->id,
                        "IsEIN" => true,
                        "EINorSSN" => $form940Record['ReturnHeader']['Business']['EINorSSN'] ?? $company->employer_identification_number,
                        "Email" => $form940Record['ReturnHeader']['Business']['Email'] ?? $company->email,
                        "ContactNm" => $form940Record['ReturnHeader']['Business']['ContactNm'] ?? $company->contact_person,
                        "Phone" => $form940Record['ReturnHeader']['Business']['Phone'] ?? $company->phone,
                        "PhoneExtn" => $form940Record['ReturnHeader']['Business']['PhoneExtn'] ?? null,
                        "Fax" => $form940Record['ReturnHeader']['Business']['Fax'] ?? $company->fax,
                        "BusinessType" => $form940Record['ReturnHeader']['Business']['BusinessType'] ?? "CORP",
                        "SigningAuthority" => [
                            "Name" => $form940Record['ReturnHeader']['Business']['SigningAuthority']['Name'] ?? $company->contact_person,
                            "Phone" => $form940Record['ReturnHeader']['Business']['SigningAuthority']['Phone'] ?? preg_replace('/[^0-9]/', '', $company->phone),
                            "BusinessMemberType" => $form940Record['ReturnHeader']['Business']['SigningAuthority']['BusinessMemberType'] ?? "TAXOFFICER"
                        ],
                        "KindOfEmployer" => $form940Record['ReturnHeader']['Business']['KindOfEmployer'] ?? null,
                        "KindOfPayer" => $form940Record['ReturnHeader']['Business']['KindOfPayer'] ?? null,
                        "IsBusinessTerminated" => $form940Record['ReturnHeader']['Business']['IsBusinessTerminated'] ?? false,
                        "IsForeign" => $form940Record['ReturnHeader']['Business']['IsForeign'] ?? false,
                        "USAddress" => [
                            "Address1" => $form940Record['ReturnHeader']['Business']['USAddress']['Address1'] ?? $company->address_line_1,
                            "Address2" => $form940Record['ReturnHeader']['Business']['USAddress']['Address2'] ?? $company->address_line_2,
                            "City" => $form940Record['ReturnHeader']['Business']['USAddress']['City'] ?? $company->city,
                            "State" => $form940Record['ReturnHeader']['Business']['USAddress']['State'] ?? $company->state,
                            "ZipCd" => $form940Record['ReturnHeader']['Business']['USAddress']['ZipCd'] ?? $company->zip_code
                        ],
                        "ForeignAddress" => $form940Record['ReturnHeader']['Business']['ForeignAddress'] ?? [
                            "Address1" => null,
                            "Address2" => null,
                            "City" => null,
                            "ProvinceOrStateNm" => null,
                            "Country" => null,
                            "PostalCd" => null
                        ]
                    ],
                    "IsThirdPartyDesignee" => $form940Record['ReturnHeader']['IsThirdPartyDesignee'] ?? true,
                    "ThirdPartyDesignee" => $form940Record['ReturnHeader']['ThirdPartyDesignee'] ?? [
                        "Name" => "Third Party Name",
                        "Phone" => "1234567890",
                        "PIN" => "12345"
                    ],
                    "SignatureDetails" => $form940Record['ReturnHeader']['SignatureDetails'] ?? [
                        "SignatureType" => "ONLINE_SIGN_PIN",
                        "OnlineSignaturePIN" => [
                            "PIN" => $company->irs_efile_pin ?? "1234567890"
                        ],
                        "ReportingAgentPIN" => [
                            "PIN" => null
                        ],
                        "Form8453EMP" => null
                    ]
                ],
                "ReturnData" => [
                    "Form940" => [
                        "OneStateCd" => $returnData['OneStateCd'] ?? null,
                        "IsCreditReduction" => $returnData['IsCreditReduction'] ?? (count($updatedScheduleA) > 0),
                        "IsSuccessorEmployer" => $returnData['IsSuccessorEmployer'] ?? false,
                        "IsPymtsMadeToEmployees" => $returnData['IsPymtsMadeToEmployees'] ?? true,
                        "IsBusinessClosed" => $returnData['IsBusinessClosed'] ?? false,
                        "IsMultiState" => $returnData['IsMultiState'] ?? (count($updatedScheduleA) > 1),
                        "WagesAmt" => round(floatval($returnData['WagesAmt'] ?? 0), 2),
                        "ExemptWagesAmt" => round(floatval($returnData['ExemptWagesAmt'] ?? 0), 2),
                        "IsFringeBenfs" => $returnData['IsFringeBenfs'] ?? false,
                        "IsGrpTermLifeIns" => $returnData['IsGrpTermLifeIns'] ?? false,
                        "IsRetrmntOrPension" => $returnData['IsRetrmntOrPension'] ?? false,
                        "IsDepCare" => $returnData['IsDepCare'] ?? false,
                        "IsOtherExempt" => $returnData['IsOtherExempt'] ?? false,
                        "WagesOverLmtAmt" => round(floatval($returnData['WagesOverLmtAmt'] ?? 0), 2),
                        "TotExemptWagesAmt" => round(floatval($returnData['TotExemptWagesAmt'] ?? 0), 2),
                        "TotTaxableWagesAmt" => round($totTaxableWagesAmt, 2),
                        "FUTATaxBeforeAdjAmt" => round($futaTaxBeforeAdjAmt, 2),
                        "MaxCreditAmt" => round($maxCreditAmt, 2),
                        "FUTAAdjAmt" => round($futaAdjAmt, 2),
                        "TotCrdtRedAmt" => round($totCrdtRedAmt, 2),
                        "FUTATaxAfterAdjAmt" => round($futaTaxAfterAdjAmt, 2),
                        "TotDepositAmt" => round($totDepositAmt, 2),
                        "FirstQtrTaxLiabilityAmt" => round($quarterlyTaxes['FirstQtrTaxLiabilityAmt'], 2),
                        "secondQtrTaxLiabilityAmt" => round($quarterlyTaxes['secondQtrTaxLiabilityAmt'], 2),
                        "ThirdQtrTaxLiabilityAmt" => round($quarterlyTaxes['ThirdQtrTaxLiabilityAmt'], 2),
                        "FourthQtrTaxLiabilityAmt" => round($quarterlyTaxes['FourthQtrTaxLiabilityAmt'], 2),
                        "TotTaxLiabilityAmt" => round($totTaxLiabilityAmt, 2),
                        "BalanceDueAmt" => round($balanceDueAmt, 2),
                        "OverPaidAmt" => round($overPaidAmt, 2),
                        "OverPaymentRecoveryType" => $returnData['OverPaymentRecoveryType'] ?? null
                    ],
                    "IRSPaymentType" => $form940Record['ReturnData']['IRSPaymentType'] ?? "EFTPS",
                    "IRSPayment" => $form940Record['ReturnData']['IRSPayment'] ?? [
                        "BankRoutingNum" => null,
                        "AccountType" => null,
                        "BankAccountNum" => null,
                        "Phone" => null
                    ],
                    "FinalPayRoll" => $form940Record['ReturnData']['FinalPayRoll'] ?? [
                        "PersonNm" => null,
                        "IsForeign" => false,
                        "USAddress" => [
                            "Address1" => null,
                            "Address2" => null,
                            "City" => null,
                            "State" => null,
                            "ZipCd" => null
                        ],
                        "ForeignAddress" => [
                            "Address1" => null,
                            "Address2" => null,
                            "City" => null,
                            "ProvinceOrStateNm" => null,
                            "Country" => null,
                            "PostalCd" => null
                        ]
                    ],
                    "ScheduleA" => $updatedScheduleA
                ]
            ]
        ]
    ];

    // Step 4: Submit Update request to TaxBandits API
    $endpoint = $apiUrl . '/Form940/Update';

    $updateResponse = Http::withHeaders([
        'Authorization' => 'Bearer ' . $accessToken,
        'Content-Type' => 'application/json',
        'Accept' => 'application/json'
    ])->put($endpoint, $requestPayload);

    if ($updateResponse->successful()) {
        $responseData = $updateResponse->json();
        return response()->json([
            'status' => 'success',
            'message' => 'Form 940 updated successfully in TaxBandits',
            'submission_id' => $submissionId,
            'record_id' => $recordId,
            'response' => $responseData
        ]);
    } else {
        $responseJson = $updateResponse->json();
        
        // Handle specific TaxBandits errors
        if (isset($responseJson['Errors']) && is_array($responseJson['Errors'])) {
            $errorMessages = [];
            foreach ($responseJson['Errors'] as $error) {
                $errorMessages[] = $error['Message'] ?? $error['Name'] ?? 'Unknown error';
            }
            
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update Form 940 in TaxBandits: ' . implode(', ', $errorMessages),
                'errors' => $responseJson['Errors'],
                'http_status' => $updateResponse->status()
            ], 400);
        }

        return response()->json([
            'status' => 'error',
            'message' => 'Failed to update Form 940 in TaxBandits',
            'error' => $responseJson['StatusMessage'] ?? $responseJson['Message'] ?? 'Unknown error',
            'http_status' => $updateResponse->status(),
            'raw_response' => $responseJson
        ], 400);
    }
}
    private function generateTaxBanditsJWT($clientId, $clientSecret, $userToken)
        {
            // JWT Header
            $header = [
                'alg' => 'HS256',
                'typ' => 'JWT'
            ];

            // JWT Payload
            $payload = [
                'iss' => $clientId,    // Issuer: Client ID
                'sub' => $clientId,    // Subject: Client ID
                'aud' => $userToken,   // Audience: User Token
                'iat' => time(),
                'exp' => time() + 300    // Issued at: Current timestamp
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


}
