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
//     $record = $data['Form940Records'][0] ?? [];
//     $year = $record['ReturnHeader']['TaxYr'] ?? now()->year;

//     // Company check
//     $company = $this->getCompany($companyId);
//     if (!$company) {
//         return response()->json(['status' => 'error', 'message' => 'Company not found'], 404);
//     }

//     // Payroll data
//     [$startDate, $endDate] = $this->getYearlyDateRange($year);
//     $salaryResource = new \Modules\IRS\Http\Resources\GetSalaryResource($company, $startDate, $endDate);
//     $salaryData = $salaryResource->toArray(request());

//     $incomeTax = floatval($salaryData['income_tax'] ?? 0);
//     $totalEmployees = intval($salaryData['total_employee'] ?? 0);

//     $additionalTaxes = $salaryData['additional_taxes'] ?? [];
//     $federalIncomeTaxWithheld = floatval($additionalTaxes['Federal Income Tax'] ?? 0);
//     $medicareWagesTipsAmt = floatval($additionalTaxes['Medicare'] ?? 0);
//     $grossPay = floatval($salaryData['gross_pay'] ?? 0);

//     // Direct from payroll → Government deductions
//     $governmentDeductions = $salaryData['government_deductions_yearly'] ?? [];
//     $StateUITax = floatval($governmentDeductions['SUTA'] ?? $governmentDeductions['ESI'] ?? 0); // SUTA
//     $Futa = floatval($governmentDeductions['FUTA'] ?? $governmentDeductions['ESI'] ?? 0); // FUTA

//     // FUTA base calculations
//     $totalGrossPay = $grossPay;
//     $totalTaxableWages = $Futa > 0 ? ($Futa / 0.006) : $grossPay; // derive wages if FUTA provided
//     $exemptWages = max(0, $totalGrossPay - $totalTaxableWages);

//     // FUTA Before Adjustment
//     $futaTaxBeforeAdj = round($totalTaxableWages * 0.006, 2);

//     // Exempt Wages checkboxes
//     $exemptCheckBoxes = [
//         'FringeBenefitsInd' => $exemptWages > 0 ? true : false,
//         'GroupTermLifeInsuranceInd' => false,
//         'RetiredPensionInd' => false,
//         'DependentCareInd' => false,
//         'OtherInd' => false,
//     ];

//     // Credit Reduction - IRS official states for 2024
//     $state = $record['ReturnHeader']['Business']['USAddress']['State'] ?? null;
    
//     // Updated credit reduction rates for 2024
//     $creditReductionStates = ['CA', 'NY', 'VI'];
//     $creditReductionRates = [
//         'CA' => 0.009, // 0.9%
//         'NY' => 0.009, // 0.9%
//         'VI' => 0.042  // 4.2%
//     ];

//     $isCreditReduction = in_array($state, $creditReductionStates);
//     $scheduleAStates = [];
//     $creditReduction = 0;

//     if ($isCreditReduction) {
//         $creditReduction = round($totalTaxableWages * ($creditReductionRates[$state] ?? 0), 2);
//         $scheduleAStates[] = [
//             'StateCd' => $state,
//             'StateAbbreviation' => $state,
//             'StateName' => $this->getStateName($state),
//             'CreditReductionAmt' => $creditReduction,
//             'CreditReductionRate' => $creditReductionRates[$state] ?? 0,
//             'TotTaxableWagesAmt' => $totalTaxableWages
//         ];
//     }

//     // FUTA After Adjustment
//     $futaTaxAfterAdj = max(0, round($futaTaxBeforeAdj - $StateUITax - $creditReduction, 2));

//     // Quarterly liability
//     $quarterly = [1 => 0, 2 => 0, 3 => 0, 4 => 0];
//     if ($futaTaxAfterAdj > 500) {
//         $perQuarter = $futaTaxAfterAdj / 4;
//         for ($i = 1; $i <= 4; $i++) $quarterly[$i] = round($perQuarter, 2);
//     } else {
//         $quarterly[4] = round($futaTaxAfterAdj, 2);
//     }

//     $totTaxLiability = array_sum($quarterly);
//     $totDeposit = floatval($record['ReturnData']['Form940']['TotDepositAmt'] ?? 0);
//     $balanceDue = max(0, $totTaxLiability - $totDeposit);
//     $overPaid = max(0, $totDeposit - $totTaxLiability);

//     // Prepare the payload according to TaxBandits API specification
//     $payload = [
//         "Form940Records" => [
//             [
//                 "Sequence" => $record['Sequence'] ?? null,
//                 "ReturnHeader" => $record['ReturnHeader'] ?? [],
//                 "ReturnData" => [
//                     "Form940" => [
//                         "IsPymtsMadeToEmployees" => true,
//                         "OneStateCd" => $state,
//                         "IsMultipleState" => false,
//                         "WagesAmt" => round($totalGrossPay, 2),
//                         "ExemptWagesAmt" => round($exemptWages, 2),
//                         "OverWageBaseAmt" => round($totalGrossPay - $totalTaxableWages, 2),
//                         "TotTaxableWagesAmt" => round($totalTaxableWages, 2),
//                         "FUTATaxBeforeAdjAmt" => $futaTaxBeforeAdj,
//                         "MaxCreditAmt" => $isCreditReduction ? 0 : round($totalTaxableWages * 0.054, 2),
//                         "StateUITaxExclusionAmt" => round($StateUITax, 2),
//                         "TotCrdtRedAmt" => $creditReduction,
//                         "FUTATaxAfterAdjAmt" => $futaTaxAfterAdj,
//                         "FirstQtrTaxLiabilityAmt" => round($quarterly[1], 2),
//                         "SecondQtrTaxLiabilityAmt" => round($quarterly[2], 2),
//                         "ThirdQtrTaxLiabilityAmt" => round($quarterly[3], 2),
//                         "FourthQtrTaxLiabilityAmt" => round($quarterly[4], 2),
//                         "TotTaxLiabilityAmt" => round($totTaxLiability, 2),
//                         "TotFUTADepositAmt" => round($totDeposit, 2),
//                         "BalanceDueAmt" => round($balanceDue, 2),
//                         "OverPaidAmt" => round($overPaid, 2),
//                         "OverPaymentRecoveryType" => ($overPaid > 0) ? "REFUND" : null,
//                         "IsCreditReduction" => $isCreditReduction,
//                         "ScheduleA" => $scheduleAStates,
//                         "FringeBenefitsInd" => $exemptCheckBoxes['FringeBenefitsInd'],
//                         "GroupTermLifeInsuranceInd" => $exemptCheckBoxes['GroupTermLifeInsuranceInd'],
//                         "RetiredPensionInd" => $exemptCheckBoxes['RetiredPensionInd'],
//                         "DependentCareInd" => $exemptCheckBoxes['DependentCareInd'],
//                         "OtherInd" => $exemptCheckBoxes['OtherInd']
//                     ],
//                     "IRSPaymentType" => ($balanceDue > 0) ? "EFTPS" : null,
//                     "IRSPayment" => ($balanceDue > 0) ? [
//                         "BankRoutingNum" => $company->bank_routing_number ?? null,
//                         "AccountType" => $company->bank_account_type ?? "CHECKING",
//                         "BankAccountNum" => $company->bank_account_number ?? null,
//                         "Phone" => $company->company_phone
//                     ] : null
//                 ]
//             ]
//         ]
//     ];
//     // Submit to TaxBandits
//     $clientId = config('services.taxbandits.client_id');
//     $clientSecret = config('services.taxbandits.client_secret');
//     $userToken = config('services.taxbandits.user_token');
//     $apiUrl = config('services.taxbandits.api_url');
//     $authUrl = config('services.taxbandits.auth_url');

//     try {
//         $jwtToken = $this->generateTaxBanditsJWT($clientId, $clientSecret, $userToken);
//         $authResponse = Http::withHeaders(['Authentication' => $jwtToken])->get($authUrl);
//         $accessToken = $authResponse->json()['AccessToken'] ?? null;

//         if (!$accessToken) {
//             throw new \Exception('Failed to obtain access token from TaxBandits');
//         }

//         $transmitResponse = Http::withHeaders([
//             'Authorization' => 'Bearer ' . $accessToken,
//             'Content-Type' => 'application/json',
//             'Accept' => 'application/json',
//         ])->timeout(60)->post($apiUrl . '/Form940/Create', $payload);

//         if ($transmitResponse->successful()) {
//             return response()->json([
//                 'status' => 'success',
//                 'message' => 'Form 940 JSON submitted successfully.',
//                 'response' => $transmitResponse->json(),
//             ]);
//         } else {
//             return response()->json([
//                 'status' => 'error',
//                 'message' => 'Failed to submit JSON to TaxBandits.',
//                 'http_status' => $transmitResponse->status(),
//                 'raw_body' => $transmitResponse->body(),
//                 'payload_sent' => $payload
//             ]);
//         }
//     } catch (\Exception $e) {
//         return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
//     }
// }

// // Helper function to get state name from abbreviation
// private function getStateName($stateAbbr)
// {
//     $states = [
//         'AL' => 'Alabama', 'AK' => 'Alaska', 'AZ' => 'Arizona', 'AR' => 'Arkansas',
//         'CA' => 'California', 'CO' => 'Colorado', 'CT' => 'Connecticut', 'DE' => 'Delaware',
//         'FL' => 'Florida', 'GA' => 'Georgia', 'HI' => 'Hawaii', 'ID' => 'Idaho',
//         'IL' => 'Illinois', 'IN' => 'Indiana', 'IA' => 'Iowa', 'KS' => 'Kansas',
//         'KY' => 'Kentucky', 'LA' => 'Louisiana', 'ME' => 'Maine', 'MD' => 'Maryland',
//         'MA' => 'Massachusetts', 'MI' => 'Michigan', 'MN' => 'Minnesota', 'MS' => 'Mississippi',
//         'MO' => 'Missouri', 'MT' => 'Montana', 'NE' => 'Nebraska', 'NV' => 'Nevada',
//         'NH' => 'New Hampshire', 'NJ' => 'New Jersey', 'NM' => 'New Mexico', 'NY' => 'New York',
//         'NC' => 'North Carolina', 'ND' => 'North Dakota', 'OH' => 'Ohio', 'OK' => 'Oklahoma',
//         'OR' => 'Oregon', 'PA' => 'Pennsylvania', 'RI' => 'Rhode Island', 'SC' => 'South Carolina',
//         'SD' => 'South Dakota', 'TN' => 'Tennessee', 'TX' => 'Texas', 'UT' => 'Utah',
//         'VT' => 'Vermont', 'VA' => 'Virginia', 'WA' => 'Washington', 'WV' => 'West Virginia',
//         'WI' => 'Wisconsin', 'WY' => 'Wyoming', 'VI' => 'Virgin Islands'
//     ];

//     return $states[strtoupper($stateAbbr)] ?? $stateAbbr;
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
            return response()->json([
                'status' => 'error',
                'message' => 'Company not found',
                'code' => 404
            ], 404);
        }

        try {
            // Payroll calculations
            [$startDate, $endDate] = $this->getYearlyDateRange($year);
            $salaryResource = new \Modules\IRS\Http\Resources\GetSalaryResource($company, $startDate, $endDate);
            $salaryData = $salaryResource->toArray(request());

            // ---------------------------
            // ✅ Ensure boolean
            // ---------------------------
            $madePayments = $record['ReturnData']['Form940']['IsPymtsMadeToEmployees'] ?? true;
            $madePayments = filter_var($madePayments, FILTER_VALIDATE_BOOLEAN);

            // Employee FUTA summary
            $employeeFutaSummary = collect($salaryData['employee_wise_taxable_gross'] ?? [])
                ->map(function($emp) {
                    $gross = floatval($emp['taxable_gross_pay'] ?? 0);
                    $taxable = min($gross, 7000);
                    return [
                        'employee_id' => $emp['employee_id'],
                        'employee_name' => $emp['employee_name'],
                        'gross_pay' => $gross,
                        'futa_taxable' => $taxable,
                        'futa_tax' => round($taxable * 0.006, 2)
                    ];
                })->filter(fn($emp) => $emp['gross_pay'] > 0)
                ->values();

            $totalWages = $employeeFutaSummary->sum('gross_pay');
            $exemptWages = floatval($salaryData['exempt_wages'] ?? 0);
            $wagesOverLimit = $employeeFutaSummary->sum(fn($emp) => max(0, $emp['gross_pay'] - 7000));
            $totalExemptWages = $exemptWages + $wagesOverLimit;
            $totalTaxableWages = $employeeFutaSummary->sum('futa_taxable');
            $futaTaxBeforeAdj = round($totalTaxableWages * 0.006, 2);

            // ------------------------------
            // Credit Reduction
            // ------------------------------
            $creditReductionRates = ['CA' => 0.009, 'NY' => 0.009, 'VI' => 0.042];
            $scheduleAData = [];
            if (!empty($record['ReturnData']['ScheduleA'])) {
                foreach ($record['ReturnData']['ScheduleA'] as $state) {
                    $stateCode = $state['StateCd'] ?? null;
                    if ($stateCode && isset($creditReductionRates[$stateCode])) {
                        $stateTaxableWages = floatval($state['TotTaxableFUTAwagesAmt'] ?? 0);
                        $rate = $creditReductionRates[$stateCode];
                        $scheduleAData[] = [
                            'StateCd' => $stateCode,
                            'CreditReductionRt' => $rate,
                            'TotTaxableFUTAwagesAmt' => $stateTaxableWages,
                            'TotCrdtRedAmt' => round($stateTaxableWages * $rate, 2)
                        ];
                    }
                }
            }

            $creditReductionAmount = array_sum(array_column($scheduleAData, 'TotCrdtRedAmt'));
            $maxCreditAmount = floatval($record['ReturnData']['Form940']['MaxCreditAmt'] ?? 0);
            $futaAdjustment = floatval($record['ReturnData']['Form940']['FUTAAdjAmt'] ?? 0);
            $futaTaxAfterAdj = $futaTaxBeforeAdj + $maxCreditAmount + $futaAdjustment + $creditReductionAmount;

            $totalDeposits = floatval($record['ReturnData']['Form940']['TotDepositAmt'] ?? 0);
            $balanceDue = max(0, $futaTaxAfterAdj - $totalDeposits);
            $overpaid = max(0, $totalDeposits - $futaTaxAfterAdj);

            // Quarterly calculation
            $quarterly = [0, 0, 0, 0];
            if ($futaTaxAfterAdj > 500) {
                $perQuarter = round($futaTaxAfterAdj / 4, 2);
                $quarterly = [$perQuarter, $perQuarter, $perQuarter, $perQuarter];
                $quarterly[3] += $futaTaxAfterAdj - array_sum($quarterly);
            } else {
                $quarterly[3] = $futaTaxAfterAdj;
            }
            $totalTaxLiability = $futaTaxAfterAdj > 500 ? $futaTaxAfterAdj : array_sum($quarterly);

            // IRS Payment
            $IRSPaymentType = $balanceDue > 0 ? ($record['ReturnData']['IRSPaymentType'] ?? 'EFTPS') : null;
            $IRSPayment = ($IRSPaymentType === 'EFW') ? [
                "BankRoutingNum" => $company->bank_routing_number,
                "AccountType" => $company->bank_account_type ?? 'CHECKING',
                "BankAccountNum" => $company->bank_account_number,
                "Phone" => preg_replace('/[^0-9]/','',$company->company_phone)
            ] : null;

            // Prepare Form940 payload
            $form940 = [
                "WagesAmt" => round($totalWages, 2),
                "ExemptWagesAmt" => round($exemptWages, 2),
                "WagesOverLmtAmt" => round($wagesOverLimit, 2),
                "TotExemptWagesAmt" => round($totalExemptWages, 2),
                "TotTaxableWagesAmt" => round($totalTaxableWages, 2),
                "FUTATaxBeforeAdjAmt" => round($futaTaxBeforeAdj, 2),
                "MaxCreditAmt" => round($maxCreditAmount, 2),
                "FUTAAdjAmt" => round($futaAdjustment, 2),
                "TotCrdtRedAmt" => round($creditReductionAmount, 2),
                "FUTATaxAfterAdjAmt" => round($futaTaxAfterAdj, 2),
                "TotDepositAmt" => round($totalDeposits, 2),
                "BalanceDueAmt" => round($balanceDue, 2),
                "OverPaidAmt" => round($overpaid, 2),
                "FirstQtrTaxLiabilityAmt" => round($quarterly[0], 2),
                "SecondQtrTaxLiabilityAmt" => round($quarterly[1], 2),
                "ThirdQtrTaxLiabilityAmt" => round($quarterly[2], 2),
                "FourthQtrTaxLiabilityAmt" => round($quarterly[3], 2),
                "TotTaxLiabilityAmt" => round($totalTaxLiability, 2),
                "IsCreditReduction" => !empty($scheduleAData),
                "ScheduleA" => $scheduleAData,
                "IsPymtsMadeToEmployees" => $madePayments
            ];

            // If NO payments, zero out values
            if (!$madePayments) {
                foreach ($form940 as $key => $val) {
                    if ($key !== 'IsPymtsMadeToEmployees') {
                        $form940[$key] = 0;
                    }
                }
                $form940['IsCreditReduction'] = false;
                $form940['ScheduleA'] = [];
                $IRSPaymentType = null;
                $IRSPayment = null;
            }

            // Auto-fill business info
            $payerRef = "IRS-9400-{$companyId}-" . now()->year . "-" . uniqid();
            $returnHeader = $record['ReturnHeader'] ?? [];
            $autoBusinessData = [
                "BusinessId" => null,
                "BusinessNm" => $company->company_name ?? 'Unknown Corp',
                "TradeNm" => $company->trade_name ?? null,
                "PayerRef" => $payerRef
            ];
            $returnHeader['Business'] = array_merge($returnHeader['Business'] ?? [], $autoBusinessData);

            $payload = [
                "Form940Records" => [[
                    "Sequence" => $record['Sequence'] ?? null,
                    "ReturnHeader" => $returnHeader,
                    "ReturnData" => [
                        "Form940" => $form940,
                        "IRSPaymentType" => $IRSPaymentType,
                        "IRSPayment" => $IRSPayment
                    ],
                    "EmployeeFUTA" => $employeeFutaSummary->toArray()
                ]]
            ];

            // TaxBandits API
            $clientId = config('services.taxbandits.client_id');
            $clientSecret = config('services.taxbandits.client_secret');
            $userToken = config('services.taxbandits.user_token');
            $apiUrl = config('services.taxbandits.api_url');
            $authUrl = config('services.taxbandits.auth_url');

            $jwtToken = $this->generateTaxBanditsJWT($clientId, $clientSecret, $userToken);
            $authResponse = Http::withHeaders(['Authentication' => $jwtToken])->get($authUrl);

            if (!$authResponse->successful()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Authentication failed',
                    'auth_response' => $authResponse->json()
                ], $authResponse->status());
            }

            $accessToken = $authResponse->json()['AccessToken'] ?? null;
            if (!$accessToken) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'AccessToken not found in auth response',
                    'auth_response' => $authResponse->json()
                ], 400);
            }

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $accessToken,
                'Content-Type' => 'application/json'
            ])->timeout(60)->post($apiUrl . '/Form940/Create', $payload);

            if ($response->successful()) {
                $responseData = $response->json();
                return response()->json([
                    'status' => 'success',
                    'message' => 'Form 940 submitted successfully',
                    'submission_id' => $responseData['Form940Records'][0]['SubmissionId'] ?? null,
                    'response' => $responseData
                ]);
            }

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to submit Form 940',
                'http_status' => $response->status(),
                'errors' => $response->json(),
                'payload_sent' => $payload
            ], $response->status());

        } catch (\Throwable $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
                'trace' => config('app.debug') ? $e->getTraceAsString() : null
            ], 500);
        }
    }

   
// public function updateForm940(Request $request)
// {
//     $data = $request->all();
//     $submissionId = $data['SubmissionId'] ?? null;
//     $record = $data['Form940Records'][0] ?? [];
//     $recordId = $record['RecordId'] ?? null;
//     $year = $record['ReturnHeader']['TaxYr'] ?? now()->year;

//     if (!$submissionId || !$recordId) {
//         return response()->json([
//             'status' => 'error',
//             'message' => 'SubmissionId and RecordId are required'
//         ], 400);
//     }

//     // Company fetch
//     $companyId = $data['company_id'] ?? null;
//     $company = $this->getCompany($companyId);
//     if (!$company) {
//         return response()->json(['status'=>'error','message'=>'Company not found'],404);
//     }

//     // Payroll data for the year
//     [$startDate, $endDate] = $this->getYearlyDateRange($year);
//     $salaryResource = new \Modules\IRS\Http\Resources\GetSalaryResource($company, $startDate, $endDate);
//     $salaryData = $salaryResource->toArray(request());

//     // Employee-wise FUTA
//     $employeeFutaSummary = collect($salaryData['employee_wise_taxable_gross'] ?? [])
//         ->map(fn($emp) => [
//             'employee_id' => $emp['employee_id'],
//             'employee_name' => $emp['employee_name'],
//             'gross_pay' => floatval($emp['taxable_gross_pay'] ?? 0),
//             'futa_taxable' => min(floatval($emp['taxable_gross_pay'] ?? 0), 7000),
//             'futa_tax' => round(min(floatval($emp['taxable_gross_pay'] ?? 0),7000) * 0.006,2)
//         ])->filter(fn($emp)=> $emp['gross_pay']>0)->values();

//     $totalGrossPay = $employeeFutaSummary->sum('gross_pay');
//     $totalTaxableWages = $employeeFutaSummary->sum('futa_taxable');

//     // FUTA Before Adjustment
//     $futaTaxBeforeAdj = floatval($record['ReturnData']['Form940']['FUTATaxBeforeAdjAmt'] ?? $employeeFutaSummary->sum('futa_tax'));

//     // State Unemployment Tax (SUTA)
//     $StateUITax = floatval($salaryData['government_deductions_yearly']['ESI'] ?? 0);

//     // Schedule A: Credit Reduction
//     $creditReductionRates = ['CA'=>0.009,'NY'=>0.009,'VI'=>0.042];
//     $scheduleA = [];
//     if(!empty($salaryData['multi_state_wages'] ?? [])) {
//         foreach($salaryData['multi_state_wages'] as $stateCode => $wages) {
//             $rate = $creditReductionRates[$stateCode] ?? 0;
//             $scheduleA[] = [
//                 'StateCd' => $stateCode,
//                 'TotTaxableFUTAwagesAmt' => round($wages,2),
//                 'CreditReductionRt' => $rate,
//                 'CreditReductionAmt' => round($wages * $rate,2)
//             ];
//         }
//     }

//     $totalCreditReduction = collect($scheduleA)->sum('CreditReductionAmt');

//     // FUTA After Adjustment
//     $futaTaxAfterAdj = floatval($record['ReturnData']['Form940']['FUTATaxAfterAdjAmt'] ?? 0);
//     if ($futaTaxAfterAdj <= 0) {
//         $futaTaxAfterAdj = max(0, round($futaTaxBeforeAdj - $StateUITax - $totalCreditReduction,2));
//     }

//     // Quarterly liability
//     $quarterly = [1=>0,2=>0,3=>0,4=>0];
//     if ($futaTaxAfterAdj > 500) {
//         $perQuarter = $futaTaxAfterAdj/4;
//         for($i=1;$i<=4;$i++) $quarterly[$i] = round($perQuarter,2);
//     } else {
//         $quarterly[4] = round($futaTaxAfterAdj,2);
//     }

//     $totTaxLiability = array_sum($quarterly);
//     $totDeposit = floatval($record['ReturnData']['Form940']['TotDepositAmt'] ?? 0);
//     $balanceDue = max(0, $totTaxLiability - $totDeposit);
//     $overPaid = max(0, $totDeposit - $totTaxLiability);

//     // Prepare payload
//     $payload = [
//         "SubmissionId" => $submissionId,
//         "Form940Records" => [
//             [
//                 "RecordId" => $recordId,
//                 "Sequence" => $record['Sequence'] ?? null,
//                 "ReturnHeader" => $record['ReturnHeader'],
//                 "ReturnData" => [
//                     "Form940" => [
//                         "WagesAmt" => round($totalGrossPay,2),
//                         "ExemptWagesAmt" => round($salaryData['exempt_wages'] ?? 0,2),
//                         "TotTaxableWagesAmt" => round($totalTaxableWages,2),
//                         "FUTATaxBeforeAdjAmt" => round($futaTaxBeforeAdj,2),
//                         "StateUITaxExclusionAmt" => round($StateUITax,2),
//                         "TotCrdtRedAmt" => round($totalCreditReduction,2),
//                         "FUTATaxAfterAdjAmt" => round($futaTaxAfterAdj,2),
//                         "FirstQtrTaxLiabilityAmt" => round($quarterly[1],2),
//                         "secondQtrTaxLiabilityAmt" => round($quarterly[2],2),
//                         "ThirdQtrTaxLiabilityAmt" => round($quarterly[3],2),
//                         "FourthQtrTaxLiabilityAmt" => round($quarterly[4],2),
//                         "TotTaxLiabilityAmt" => round($totTaxLiability,2),
//                         "TotDepositAmt" => round($totDeposit,2),
//                         "BalanceDueAmt" => round($balanceDue,2),
//                         "OverPaidAmt" => round($overPaid,2)
//                     ],
//                     "ScheduleA" => $scheduleA
//                 ],
//                 "EmployeeFUTA" => $employeeFutaSummary
//             ]
//         ]
//     ];

//     // Submit to TaxBandits Update API
//     $clientId = config('services.taxbandits.client_id');
//     $clientSecret = config('services.taxbandits.client_secret');
//     $userToken = config('services.taxbandits.user_token');
//     $apiUrl = config('services.taxbandits.api_url');
//     $authUrl = config('services.taxbandits.auth_url');

//     try {
//         $jwtToken = $this->generateTaxBanditsJWT($clientId,$clientSecret,$userToken);
//         $authResponse = Http::withHeaders(['Authentication'=>$jwtToken])->get($authUrl);
//         $accessToken = $authResponse->json()['AccessToken'] ?? null;

//         $response = Http::withHeaders([
//             'Authorization'=>'Bearer '.$accessToken,
//             'Content-Type'=>'application/json'
//         ])->timeout(60)->put($apiUrl.'/Form940/Update',$payload);

//         if ($response->successful()) {
//             return response()->json([
//                 'status'=>'success',
//                 'message'=>'Form 940 updated successfully',
//                 'response'=>$response->json()
//             ]);
//         } else {
//             return response()->json([
//                 'status'=>'error',
//                 'message'=>'Failed to update Form 940',
//                 'http_status'=>$response->status(),
//                 'errors'=>$response->json()['Errors'] ?? [],
//                 'payload'=>$payload
//             ],400);
//         }
//     } catch (\Exception $e) {
//         return response()->json(['status'=>'error','message'=>$e->getMessage()],500);
//     }
// }

public function updateForm940(Request $request)
{
    $data = $request->all();
    $companyId = auth()->user()->company_id;
    $submissionId = $data['SubmissionId'] ?? null;
    $recordId = $data['RecordId'] ?? null;
    $record = $data['Form940Records'][0] ?? [];
    $recordId = $record['RecordId'] ?? null;
    $year = $record['ReturnHeader']['TaxYr'] ?? now()->year;

    // Validation
    if (!$submissionId) {
        return response()->json([
            'status' => 'error',
            'message' => 'Submission ID is required for update',
            'code' => 400
        ], 400);
    }

    if (!$recordId) {
        return response()->json([
            'status' => 'error',
            'message' => 'Record ID is required for update',
            'code' => 400
        ], 400);
    }

    // Company check
    $company = $this->getCompany($companyId);
    if (!$company) {
        return response()->json([
            'status' => 'error',
            'message' => 'Company not found',
            'code' => 404
        ], 404);
    }

    try {
        // Payroll calculations
        [$startDate, $endDate] = $this->getYearlyDateRange($year);
        $salaryResource = new \Modules\IRS\Http\Resources\GetSalaryResource($company, $startDate, $endDate);
        $salaryData = $salaryResource->toArray(request());

        // ---------------------------
        // ✅ Ensure boolean
        // ---------------------------
        $madePayments = $record['ReturnData']['Form940']['IsPymtsMadeToEmployees'] ?? true;
        $madePayments = filter_var($madePayments, FILTER_VALIDATE_BOOLEAN);

        // Employee FUTA summary
        $employeeFutaSummary = collect($salaryData['employee_wise_taxable_gross'] ?? [])
            ->map(function($emp) {
                $gross = floatval($emp['taxable_gross_pay'] ?? 0);
                $taxable = min($gross, 7000);
                return [
                    'employee_id' => $emp['employee_id'],
                    'employee_name' => $emp['employee_name'],
                    'gross_pay' => $gross,
                    'futa_taxable' => $taxable,
                    'futa_tax' => round($taxable * 0.006, 2)
                ];
            })->filter(fn($emp) => $emp['gross_pay'] > 0)
            ->values();

        $totalWages = $employeeFutaSummary->sum('gross_pay');
        $exemptWages = floatval($salaryData['exempt_wages'] ?? 0);
        $wagesOverLimit = $employeeFutaSummary->sum(fn($emp) => max(0, $emp['gross_pay'] - 7000));
        $totalExemptWages = $exemptWages + $wagesOverLimit;
        $totalTaxableWages = $employeeFutaSummary->sum('futa_taxable');
        $futaTaxBeforeAdj = round($totalTaxableWages * 0.006, 2);

        // ------------------------------
        // Credit Reduction - Updated rates for 2024
        // ------------------------------
        $creditReductionRates = ['CA' => 0.009, 'NY' => 0.009, 'VI' => 0.042];
        $scheduleAData = [];
        if (!empty($record['ReturnData']['ScheduleA'])) {
            foreach ($record['ReturnData']['ScheduleA'] as $state) {
                $stateCode = $state['StateCd'] ?? null;
                if ($stateCode && isset($creditReductionRates[$stateCode])) {
                    $stateTaxableWages = floatval($state['TotTaxableFUTAwagesAmt'] ?? 0);
                    $rate = $creditReductionRates[$stateCode];
                    $scheduleAData[] = [
                        'StateCd' => $stateCode,
                        'CreditReductionRt' => $rate,
                        'TotTaxableFUTAwagesAmt' => $stateTaxableWages,
                        'CreditReductionAmt' => round($stateTaxableWages * $rate, 2)
                    ];
                }
            }
        }

        $creditReductionAmount = array_sum(array_column($scheduleAData, 'CreditReductionAmt'));
        $maxCreditAmount = floatval($record['ReturnData']['Form940']['MaxCreditAmt'] ?? 0);
        $futaAdjustment = floatval($record['ReturnData']['Form940']['FUTAAdjAmt'] ?? 0);
        $futaTaxAfterAdj = $futaTaxBeforeAdj + $maxCreditAmount + $futaAdjustment + $creditReductionAmount;

        $totalDeposits = floatval($record['ReturnData']['Form940']['TotDepositAmt'] ?? 0);
        $balanceDue = max(0, $futaTaxAfterAdj - $totalDeposits);
        $overpaid = max(0, $totalDeposits - $futaTaxAfterAdj);

        // Quarterly calculation
        $quarterly = [0, 0, 0, 0];
        if ($futaTaxAfterAdj > 500) {
            $perQuarter = round($futaTaxAfterAdj / 4, 2);
            $quarterly = [$perQuarter, $perQuarter, $perQuarter, $perQuarter];
            $quarterly[3] += $futaTaxAfterAdj - array_sum($quarterly);
        } else {
            $quarterly[3] = $futaTaxAfterAdj;
        }
        $totalTaxLiability = $futaTaxAfterAdj > 500 ? $futaTaxAfterAdj : array_sum($quarterly);

        // IRS Payment
        $IRSPaymentType = $balanceDue > 0 ? ($record['ReturnData']['IRSPaymentType'] ?? 'EFTPS') : null;
        $IRSPayment = ($IRSPaymentType === 'EFW') ? [
            "BankRoutingNum" => $company->bank_routing_number,
            "AccountType" => $company->bank_account_type ?? 'CHECKING',
            "BankAccountNum" => $company->bank_account_number,
            "Phone" => preg_replace('/[^0-9]/','',$company->company_phone)
        ] : null;

        // Check exempt wage checkboxes
        $isFringeBenfs = $record['ReturnData']['Form940']['IsFringeBenfs'] ?? false;
        $isGrpTermLifeIns = $record['ReturnData']['Form940']['IsGrpTermLifeIns'] ?? false;
        $isRetrmntOrPension = $record['ReturnData']['Form940']['IsRetrmntOrPension'] ?? false;
        $isDepCare = $record['ReturnData']['Form940']['IsDepCare'] ?? false;
        $isOtherExempt = $record['ReturnData']['Form940']['IsOtherExempt'] ?? false;
        
        // Convert to boolean if they're strings
        $isFringeBenfs = filter_var($isFringeBenfs, FILTER_VALIDATE_BOOLEAN);
        $isGrpTermLifeIns = filter_var($isGrpTermLifeIns, FILTER_VALIDATE_BOOLEAN);
        $isRetrmntOrPension = filter_var($isRetrmntOrPension, FILTER_VALIDATE_BOOLEAN);
        $isDepCare = filter_var($isDepCare, FILTER_VALIDATE_BOOLEAN);
        $isOtherExempt = filter_var($isOtherExempt, FILTER_VALIDATE_BOOLEAN);
        
        // If any exempt checkbox is true, ensure ExemptWagesAmt is not zero
        $hasExemptCategories = $isFringeBenfs || $isGrpTermLifeIns || $isRetrmntOrPension || $isDepCare || $isOtherExempt;
        if ($hasExemptCategories && $exemptWages <= 0) {
            // If user marked exempt categories but didn't provide exempt wages, 
            // we should either get it from request or set a minimum value
            $requestExemptWages = floatval($record['ReturnData']['Form940']['ExemptWagesAmt'] ?? 0);
            if ($requestExemptWages > 0) {
                $exemptWages = $requestExemptWages;
            } else {
                // Log warning and reset checkboxes to false if no exempt wages provided
                $isFringeBenfs = false;
                $isGrpTermLifeIns = false;
                $isRetrmntOrPension = false;
                $isDepCare = false;
                $isOtherExempt = false;
            }
        }

        // Prepare Form940 payload
        $form940 = [
            "OneStateCd" => $record['ReturnData']['Form940']['OneStateCd'] ?? null,
            "WagesAmt" => round($totalWages, 2),
            "ExemptWagesAmt" => round($exemptWages, 2),
            "IsFringeBenfs" => $isFringeBenfs,
            "IsGrpTermLifeIns" => $isGrpTermLifeIns,
            "IsRetrmntOrPension" => $isRetrmntOrPension,
            "IsDepCare" => $isDepCare,
            "IsOtherExempt" => $isOtherExempt,
            "WagesOverLmtAmt" => round($wagesOverLimit, 2),
            "TotExemptWagesAmt" => round($totalExemptWages, 2),
            "TotTaxableWagesAmt" => round($totalTaxableWages, 2),
            "FUTATaxBeforeAdjAmt" => round($futaTaxBeforeAdj, 2),
            "MaxCreditAmt" => round($maxCreditAmount, 2),
            "FUTAAdjAmt" => round($futaAdjustment, 2),
            "TotCrdtRedAmt" => round($creditReductionAmount, 2),
            "FUTATaxAfterAdjAmt" => round($futaTaxAfterAdj, 2),
            "TotDepositAmt" => round($totalDeposits, 2),
            "BalanceDueAmt" => round($balanceDue, 2),
            "OverPaidAmt" => round($overpaid, 2),
            "FirstQtrTaxLiabilityAmt" => round($quarterly[0], 2),
            "SecondQtrTaxLiabilityAmt" => round($quarterly[1], 2),
            "ThirdQtrTaxLiabilityAmt" => round($quarterly[2], 2),
            "FourthQtrTaxLiabilityAmt" => round($quarterly[3], 2),
            "TotTaxLiabilityAmt" => round($totalTaxLiability, 2),
            "IsCreditReduction" => !empty($scheduleAData),
            "IsSuccessorEmployer" => $record['ReturnData']['Form940']['IsSuccessorEmployer'] ?? false,
            "IsPymtsMadeToEmployees" => $madePayments,
            "IsBusinessClosed" => $record['ReturnData']['Form940']['IsBusinessClosed'] ?? false,
            "IsMultiState" => $record['ReturnData']['Form940']['IsMultiState'] ?? false,
            "OverPaymentRecoveryType" => $record['ReturnData']['Form940']['OverPaymentRecoveryType'] ?? null
        ];

        // If NO payments, zero out values
        if (!$madePayments) {
            foreach ($form940 as $key => $val) {
                if (!in_array($key, ['IsPymtsMadeToEmployees', 'IsBusinessClosed', 'IsSuccessorEmployer', 'IsMultiState', 'OneStateCd', 'IsFringeBenfs', 'IsGrpTermLifeIns', 'IsRetrmntOrPension', 'IsDepCare', 'IsOtherExempt', 'OverPaymentRecoveryType'])) {
                    $form940[$key] = is_bool($val) ? false : 0;
                }
            }
            $form940['IsCreditReduction'] = false;
            $scheduleAData = [];
            $IRSPaymentType = null;
            $IRSPayment = null;
        }

        // Auto-fill business info
        $payerRef = "IRS-9400-{$companyId}-" . $year . "-" . uniqid();
        $returnHeader = $record['ReturnHeader'] ?? [];
        $autoBusinessData = [
            "BusinessId" => null,
            "BusinessNm" => $company->company_name ?? 'Unknown Corp',
            "TradeNm" => $company->trade_name ?? null,
            "PayerRef" => $payerRef
        ];
        $returnHeader['Business'] = array_merge($returnHeader['Business'] ?? [], $autoBusinessData);

        // Update payload structure
        $payload = [
            "SubmissionId" => $submissionId,
            "Form940Records" => [[
                "RecordId" => $recordId,
                "Sequence" => $record['Sequence'] ?? null,
                "ReturnHeader" => $returnHeader,
                "ReturnData" => [
                    "Form940" => $form940,
                    "IRSPaymentType" => $IRSPaymentType,
                    "IRSPayment" => $IRSPayment,
                    "FinalPayRoll" => $record['ReturnData']['FinalPayRoll'] ?? null,
                    "ScheduleA" => $scheduleAData
                ]
            ]]
        ];

        // TaxBandits API
        $clientId = config('services.taxbandits.client_id');
        $clientSecret = config('services.taxbandits.client_secret');
        $userToken = config('services.taxbandits.user_token');
        $apiUrl = config('services.taxbandits.api_url');
        $authUrl = config('services.taxbandits.auth_url');

        $jwtToken = $this->generateTaxBanditsJWT($clientId, $clientSecret, $userToken);
        $authResponse = Http::withHeaders(['Authentication' => $jwtToken])->get($authUrl);

        if (!$authResponse->successful()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Authentication failed',
                'auth_response' => $authResponse->json()
            ], $authResponse->status());
        }

        $accessToken = $authResponse->json()['AccessToken'] ?? null;
        if (!$accessToken) {
            return response()->json([
                'status' => 'error',
                'message' => 'AccessToken not found in auth response',
                'auth_response' => $authResponse->json()
            ], 400);
        }

        // Make UPDATE request to TaxBandits API
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $accessToken,
            'Content-Type' => 'application/json'
        ])->timeout(60)->put($apiUrl . '/Form940/Update', $payload);

        if ($response->successful()) {
            $responseData = $response->json();
            return response()->json([
                'status' => 'success',
                'message' => 'Form 940 updated successfully',
                'submission_id' => $responseData['SubmissionId'] ?? $submissionId,
                'updated_records' => $responseData['Form940Records']['SuccessRecords'] ?? [],
                'response' => $responseData
            ]);
        }

        return response()->json([
            'status' => 'error',
            'message' => 'Failed to update Form 940',
            'http_status' => $response->status(),
            'errors' => $response->json(),
            'payload_sent' => $payload
        ], $response->status());

    } catch (\Throwable $e) {
        return response()->json([
            'status' => 'error',
            'message' => $e->getMessage(),
            'trace' => config('app.debug') ? $e->getTraceAsString() : null
        ], 500);
    }
}

public function validateForm940(Request $request)
{
    $data = $request->all();
    $submissionId = $data['submission_id'] ?? $data['SubmissionId'] ?? null;
    $recordIds = $data['record_ids'] ?? $data['RecordIds'] ?? null;

    if (!$submissionId) {
        return response()->json([
            'status' => 'error',
            'message' => 'Submission ID is required for validation',
            'code' => 400
        ], 400);
    }

    try {
        // TaxBandits API Configuration
        $clientId = config('services.taxbandits.client_id');
        $clientSecret = config('services.taxbandits.client_secret');
        $userToken = config('services.taxbandits.user_token');
        $apiUrl = config('services.taxbandits.api_url');
        $authUrl = config('services.taxbandits.auth_url');

        // -----------------------------
        // JWT Token generation with try-catch
        // -----------------------------
        try {
            $jwtToken = $this->generateTaxBanditsJWT($clientId, $clientSecret, $userToken);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to generate JWT token: ' . $e->getMessage(),
            ], 500);
        }

        // Authenticate to get AccessToken
        $authResponse = Http::withHeaders(['Authentication' => $jwtToken])->get($authUrl);

        if (!$authResponse->successful()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Authentication failed',
                'auth_response' => $authResponse->json()
            ], $authResponse->status());
        }

        $accessToken = $authResponse->json()['AccessToken'] ?? null;
        if (!$accessToken) {
            return response()->json([
                'status' => 'error',
                'message' => 'AccessToken not found in auth response',
                'auth_response' => $authResponse->json()
            ], 400);
        }

        // Build validation URL
        $validateUrl = $apiUrl . '/Form940/Validate?SubmissionId=' . $submissionId;
        if (!empty($recordIds)) {
            if (is_array($recordIds)) {
                $recordIds = implode(',', $recordIds);
            }
            $validateUrl .= '&RecordIds=' . $recordIds;
        }

        // Call TaxBandits validation endpoint
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $accessToken,
            'Content-Type' => 'application/json'
        ])->timeout(60)->get($validateUrl);

        $responseData = $response->json();

        if ($response->successful()) {
            $hasErrors = !empty($responseData['Form940Records']['ErrorRecords'] ?? []) || 
                         !empty($responseData['Errors'] ?? []);
            
            $successRecords = $responseData['Form940Records']['SuccessRecords'] ?? [];
            $errorRecords = $responseData['Form940Records']['ErrorRecords'] ?? [];
            $generalErrors = $responseData['Errors'] ?? [];

            return response()->json([
                'status' => $hasErrors ? 'error' : 'success',
                'message' => $hasErrors ? 'Form 940 validation completed with errors' : 'Form 940 validation successful',
                'submission_id' => $responseData['SubmissionId'] ?? $submissionId,
                'validation_results' => [
                    'success_records' => $successRecords,
                    'error_records' => $errorRecords,
                    'general_errors' => $generalErrors,
                    'total_success' => count($successRecords),
                    'total_errors' => count($errorRecords)
                ],
                'response' => $responseData
            ]);
        }

        return response()->json([
            'status' => 'error',
            'message' => 'Failed to validate Form 940',
            'http_status' => $response->status(),
            'errors' => $responseData,
            'validation_url' => $validateUrl
        ], $response->status());

    } catch (\Throwable $e) {
        return response()->json([
            'status' => 'error',
            'message' => 'Validation request failed: ' . $e->getMessage(),
            'trace' => config('app.debug') ? $e->getTraceAsString() : null
        ], 500);
    }
}


public function validateForm940JsonToTaxBandits(Request $request)
{
    $data = $request->all();
    $companyId = $data['company_id'] ?? null;
    $record = $data['Form940Records'][0] ?? [];
    $year = $record['ReturnHeader']['TaxYr'] ?? now()->year;

    // Company check
    $company = $this->getCompany($companyId);
    if (!$company) {
        return response()->json(['status'=>'error','message'=>'Company not found'],404);
    }

    // Payroll data
    [$startDate, $endDate] = $this->getYearlyDateRange($year);
    $salaryResource = new \Modules\IRS\Http\Resources\GetSalaryResource($company, $startDate, $endDate);
    $salaryData = $salaryResource->toArray(request());

    // Employee-wise FUTA
    $employeeFutaSummary = collect($salaryData['employee_wise_taxable_gross'] ?? [])
        ->map(function($emp){
            $gross = floatval($emp['taxable_gross_pay'] ?? 0);
            $taxable = min($gross, 7000); // FUTA cap per employee
            $futaTax = round($taxable * 0.006,2); // FUTA 0.6%
            return [
                'employee_id' => $emp['employee_id'],
                'employee_name' => $emp['employee_name'],
                'gross_pay' => $gross,
                'futa_taxable' => $taxable,
                'futa_tax' => $futaTax
            ];
        })->filter(fn($emp)=> $emp['gross_pay']>0)->values();

    $totalGrossPay = $employeeFutaSummary->sum('gross_pay');
    $totalTaxableWages = $employeeFutaSummary->sum('futa_taxable');

    // FUTA Before Adjustment
    $futaTaxBeforeAdj = floatval($record['ReturnData']['Form940']['FUTATaxBeforeAdjAmt'] ?? $employeeFutaSummary->sum('futa_tax'));

    // State Unemployment Tax (SUTA)
    $governmentDeductions = $salaryData['government_deductions_yearly'] ?? [];
    $StateUITax = floatval($governmentDeductions['ESI'] ?? 0);

    // Schedule A: Credit Reduction
    $state = $record['ReturnHeader']['Business']['USAddress']['State'] ?? null;
    $creditReductionRates = ['CA'=>0.009,'NY'=>0.009,'VI'=>0.042];
    $creditReduction = round($totalTaxableWages * ($creditReductionRates[$state] ?? 0),2);

    // FUTA After Adjustment
    $futaTaxAfterAdj = floatval($record['ReturnData']['Form940']['FUTATaxAfterAdjAmt'] ?? 0);
    if ($futaTaxAfterAdj <= 0) {
        $futaTaxAfterAdj = max(0, round($futaTaxBeforeAdj - $StateUITax - $creditReduction,2));
    }

    // Quarterly liability
    $quarterly = [1=>0,2=>0,3=>0,4=>0];
    if ($futaTaxAfterAdj > 500) {
        $perQuarter = $futaTaxAfterAdj/4;
        for($i=1;$i<=4;$i++) $quarterly[$i] = round($perQuarter,2);
    } else {
        $quarterly[4] = round($futaTaxAfterAdj,2);
    }

    $totTaxLiability = array_sum($quarterly);
    $totDeposit = floatval($record['ReturnData']['Form940']['TotDepositAmt'] ?? 0);
    $balanceDue = max(0, $totTaxLiability - $totDeposit);
    $overPaid = max(0, $totDeposit - $totTaxLiability);

    // Prepare payload for ValidateForm
    // Safe nested access
$business = $record['ReturnHeader']['Business'] ?? [];
$signingAuthority = $business['SigningAuthority'] ?? [];
$signatureDetails = $record['ReturnHeader']['SignatureDetails'] ?? [];
$thirdParty = $record['ReturnHeader']['ThirdPartyDesignee'] ?? [];

    $payload = [
    "Form940Records" => [
        [
            "Sequence" => $record['Sequence'] ?? null,
            "ReturnHeader" => [
                "ReturnType" => "FORM940",
                "TaxYr" => (string)($record['ReturnHeader']['TaxYr'] ?? $year),
                "Business" => [
                    "BusinessId" => null,
                    "BusinessNm" => $salaryData['company_name'] ?? $company->name,
                    "PayerRef" => "ytyuty78656",
                    "IsEIN" => true,
                    "EINorSSN" => $salaryData['employer_identification_number'] ?? $company->employer_identification_number,
                    "Email" => $salaryData['company_email'] ?? $company->company_email,
                    "Phone" => $salaryData['company_phone'] ?? $company->company_phone,
                    "BusinessType" => $record['ReturnHeader']['Business']['BusinessType'] ?? 'CORP',
                    "USAddress" => $record['ReturnHeader']['Business']['USAddress'] ?? []
                ],
                "SigningAuthority" => [
                        "Name" => $signingAuthority['Name'] ?? 'Company Owner',
                        "Phone" => preg_replace('/[^0-9]/','',$signingAuthority['Phone'] ?? '1234567890'),
                        "BusinessMemberType" => $signingAuthority['BusinessMemberType'] ?? 'TAXOFFICER'
                    ],
                "SignatureDetails" => [
                    "SignatureType" => $signatureDetails['SignatureType'] ?? 'ONLINE_SIGN_PIN',
                    "OnlineSignaturePIN" => [
                        "PIN" => $signatureDetails['OnlineSignaturePIN']['PIN'] ?? '123456'
                    ]
                ],
                "IsThirdPartyDesignee" => $record['ReturnHeader']['IsThirdPartyDesignee'] ?? false
            ],
            "ReturnData" => [
                "Form940" => [
                    "WagesAmt" => round($totalGrossPay ?? 0,2),
                    "ExemptWagesAmt" => round($salaryData['exempt_wages'] ?? 0,2),
                    "TotTaxableWagesAmt" => round($totalTaxableWages ?? 0,2),
                    "FUTATaxBeforeAdjAmt" => round($futaTaxBeforeAdj ?? 0,2),
                    "StateUITaxExclusionAmt" => round($StateUITax ?? 0,2),
                    "TotCrdtRedAmt" => round($creditReduction ?? 0,2),
                    "FUTATaxAfterAdjAmt" => round($futaTaxAfterAdj ?? 0,2),
                    "FirstQtrTaxLiabilityAmt" => round($quarterly[1] ?? 0,2),
                    "SecondQtrTaxLiabilityAmt" => round($quarterly[2] ?? 0,2),
                    "ThirdQtrTaxLiabilityAmt" => round($quarterly[3] ?? 0,2),
                    "FourthQtrTaxLiabilityAmt" => round($quarterly[4] ?? 0,2),
                    "TotTaxLiabilityAmt" => round($totTaxLiability ?? 0,2),
                    "TotFUTADepositAmt" => round($totDeposit ?? 0,2),
                    "BalanceDueAmt" => round($balanceDue ?? 0,2),
                    "OverPaidAmt" => round($overPaid ?? 0,2)
                ]
            ],
            "EmployeeFUTA" => $employeeFutaSummary->toArray() // convert collection to array
        ]
    ]
];

    // TaxBandits credentials
    $clientId = config('services.taxbandits.client_id');
    $clientSecret = config('services.taxbandits.client_secret');
    $userToken = config('services.taxbandits.user_token');
    $authUrl = config('services.taxbandits.auth_url');
    $apiUrl = config('services.taxbandits.api_url');

    // Generate JWT token
    try {
        $jwtToken = $this->generateTaxBanditsJWT($clientId, $clientSecret, $userToken);
    } catch (\Exception $e) {
        return response()->json([
            'status' => 'error',
            'message' => 'Failed to generate JWT token: ' . $e->getMessage(),
        ], 500);
    }

    // Get access token
    $authResponse = Http::withHeaders([
            'Authentication' => $jwtToken,
        ])->get($authUrl);

    if ($authResponse->failed()) {
        return response()->json([
            'status' => 'error',
            'message' => 'TaxBandits authentication failed.',
            'details' => $authResponse->json(),
        ], 401);
    }

    $accessToken = $authResponse->json('AccessToken');
    if (!$accessToken) {
        return response()->json([
            'status' => 'error',
            'message' => 'Failed to get access token from TaxBandits.',
            'response' => $authResponse->json(),
        ], 401);
    }

    // Call ValidateForm API
    try {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer '.$accessToken,
            'Content-Type' => 'application/json',
        ])->post($apiUrl.'/Form940/ValidateForm', $payload);

        if ($response->successful()) {
            return response()->json([
                'status' => 'success',
                'message' => 'Form 940 validated successfully',
                'response' => $response->json()
            ]);
        } else {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'http_status' => $response->status(),
                'errors' => $response->json()['Errors'] ?? [],
                'payload' => $payload
            ], 400);
        }
    } catch (\Exception $e) {
        return response()->json([
            'status' => 'error',
            'message' => 'API call failed: '.$e->getMessage()
        ], 500);
    }
}



    public function listForm940(Request $request)
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
        $endpoint = $apiUrl . '/Form940/List';

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
                    'message' => 'Form 940 returns retrieved successfully.',
                    'data' => $responseData,
                    'query_params' => $queryParams, // For debugging
                ]);
            } else {
                return response()->json(
                    [
                        'status' => 'error',
                        'message' => 'Failed to retrieve Form 940 returns from TaxBandits.',
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
                    'message' => 'Exception occurred while listing Form 940 returns: ' . $e->getMessage(),
                ],
                500,
            );
        }
    }

    public function getForm940(Request $request)
    {
        
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

        // Required param: SubmissionId
        $submissionId = $request->get('SubmissionId');
        if (empty($submissionId)) {
            return response()->json([
                'status' => 'error',
                'message' => 'SubmissionId parameter is required.',
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

        // Step 3: Build query params exactly as per TaxBandits docs
        $queryParams = [
            'SubmissionId' => $submissionId,
        ];

        // Optional: RecordIds (comma-separated GUIDs)
        if ($request->filled('RecordIds')) {
            $queryParams['RecordIds'] = $request->get('RecordIds');
        }

        // Step 4: Request to Form940/Get
        $endpoint = rtrim($apiUrl, '/') . '/Form940/Get';
        $url = $endpoint . '?' . http_build_query($queryParams);

        try {
            $getResponse = Http::withHeaders([
                'Authorization' => 'Bearer ' . $accessToken,
                'Accept' => 'application/json',
            ])->get($url);

            if ($getResponse->successful()) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Form 940 information retrieved successfully.',
                    'data' => $getResponse->json(), // full API response (matches doc structure)
                    'query_params' => $queryParams, // debug info
                ]);
            } else {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Failed to retrieve Form 940 information.',
                    'http_status' => $getResponse->status(),
                    'error_response' => $getResponse->json(),
                    'query_params' => $queryParams,
                ], $getResponse->status());
            }
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Exception occurred while retrieving Form 940 information: ' . $e->getMessage(),
            ], 500);
        }
    }


    public function deleteForm940(Request $request)
    {
        $request->validate([
            'submission_id' => 'required|uuid',
            'record_id' => 'nullable|uuid',
        ]);

        $submissionId = $request->submission_id;
        $recordId = $request->record_id;

        // TaxBandits Credentials
        $clientId = config('services.taxbandits.client_id');
        $clientSecret = config('services.taxbandits.client_secret');
        $userToken = config('services.taxbandits.user_token');
        $apiUrl = rtrim(config('services.taxbandits.api_url'), '/');

        // Generate JWT token
        $jwtToken = $this->generateTaxBanditsJWT($clientId, $clientSecret, $userToken);

        // Endpoint URL
        $endpoint = $apiUrl . "/Form940/Delete?SubmissionId={$submissionId}";
        if (!empty($recordId)) {
            $endpoint .= "&Recordids={$recordId}";
        }

        // DELETE Request
        $response = Http::withHeaders([
            'Authorization' => "Bearer {$jwtToken}",
            'Content-Type' => 'application/json',
        ])->delete($endpoint);

        $responseBody = $response->json();

        // Final API Response format
        $apiResponse = [
            'status' => $response->successful() ? 'success' : 'error',
            'message' => $response->successful() ? 'Form 940 return(s) deleted successfully from TaxBandits.' : 'Failed to delete Form 941 return(s) from TaxBandits.',
            'http_status' => $response->status(),
            'api_response' => $responseBody,
        ];

        return response()->json($apiResponse, $response->status());
    }


    public function getForm940Status(Request $request)
    {
        $request->validate([
            'submission_id' => 'required|uuid',  
            'record_id' => 'nullable|uuid',      
        ]);

        $submissionId = $request->submission_id;
        $recordId = $request->record_id;

        $clientId = config('services.taxbandits.client_id');
        $clientSecret = config('services.taxbandits.client_secret');
        $userToken = config('services.taxbandits.user_token');
        $apiUrl = rtrim(config('services.taxbandits.api_url'), '/');

        $jwtToken = $this->generateTaxBanditsJWT($clientId, $clientSecret, $userToken);

        // Form940 Status API
        $endpoint = $apiUrl . "/Form940/status?SubmissionId={$submissionId}";
        if (!empty($recordId)) {
            $endpoint .= "&RecordIds={$recordId}";
        }

        $response = Http::withHeaders([
            'Authorization' => "Bearer {$jwtToken}",
            'Content-Type' => 'application/json',
        ])->get($endpoint);

        $data = $response->json();

        $statusCode = $data['StatusCode'] ?? null;
        $statusName = $data['Form940Records']['SuccessRecords'][0]['RecordStatus'] ?? null;
        $isPending = in_array(strtolower($statusName), ['created', 'inprogress']);

        return response()->json([
            'status' => $response->successful() ? 'success' : 'error',
            'status_code' => $statusCode,
            'record_status' => $statusName,
            'is_pending' => $isPending,
            'raw_data' => $data,
            'http_status' => $response->status(),
        ], $response->status());
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
