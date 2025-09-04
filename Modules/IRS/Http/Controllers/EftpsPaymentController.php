<?php
namespace Modules\IRS\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\IRS\Entities\EftpsPayment;
use Modules\IRS\Http\Services\TaxBanditsService;

class EftpsPaymentController extends Controller
{
    protected $taxBandits;

    public function __construct(TaxBanditsService $taxBandits)
    {
        $this->taxBandits = $taxBandits;
    }

    // Manual Pay (API)
    // public function manualPay(Request $request)
    // {
    //     $request->validate([
    //         'tax_type' => 'required|string',
    //         'amount' => 'required|numeric|min:1',
    //         'period' => 'required|string',
    //         'payment_mode' => 'nullable|string'
    //     ]);

    //     $companyId = auth()->user()->company_id;
    //     $response = $this->taxBandits->createEftpsPayment(
    //         $companyId,
    //         $request->tax_type,
    //         $request->amount,
    //         $request->period
    //     );

    //     $payment = EftpsPayment::create([
    //         'company_id'   => $companyId,
    //         'tax_type'     => $request->tax_type,
    //         'amount'       => $request->amount,
    //         'period'       => $request->period,
    //         'submission_id'=> $response['SubmissionId'] ?? null,
    //         'status'       => $response['Status'] ?? 'Submitted',
    //         'payment_mode' => $request->payment_mode ?? 'manual'
    //     ]);

    //     return response()->json([
    //         'status' => 'success',
    //         'message' => 'EFTPS Payment submitted',
    //         'payment' => $payment,
    //         'response' => $response
    //     ]);
    // }

    public function manualPay(Request $request)
    {
        $request->validate([
            'tax_type' => 'required|string',
            'amount' => 'required|numeric|min:1',
            'period' => 'required|string',
            'payment_date' => 'required|date|date_format:Y-m-d',
            //'payment_mode' => 'nullable|in:manual,auto',
            'confirmation_number' => 'required|string',
            
        ]);
       

        $companyId = auth()->user()->company_id;

        try {
            $response = $this->taxBandits->createEftpsPayment(
                $companyId,
                $request->tax_type,
                $request->amount,
                $request->period,
                $request->confirmation_number
            );

            $payment = EftpsPayment::create([
                'company_id'   => $companyId,
                'tax_type'     => $request->tax_type,
                'amount'       => $request->amount,
                'period'       => $request->period,
                'submission_id'=> $response['SubmissionId'] ?? null,
                'status'       => $response['Status'] ?? 'Completed',
               // 'payment_mode'       => $response['payment_mode'] ?? 'Manual',
                'payment_mode' => $request->payment_mode ?? 'Manual',
                'confirmation_number' => $request->confirmation_number,
                'payment_date' => $request->payment_date
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'EFTPS Payment submitted',
                'payment' => $payment,
                'response' => $response
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Payment failed: ' . $e->getMessage()
            ], 500);
        }
    }

    // Auto Pay (Finalized Payroll)
    public function autoPay(Request $request)
    {
        $request->validate([
        'taxType' => 'required|string',
        'amount'  => 'required|numeric|min:1',
        'period'  => 'required|string',
        ]);

        $taxType = $request->taxType;
        $amount  = $request->amount;
        $period  = $request->period;
        $companyId = auth()->user()->company_id;

        $response = $this->taxBandits->createEftpsPayment(
            $companyId, $taxType, $amount, $period, $confirmation_number
        );
      
        if (!$response || !isset($response['SubmissionId'])) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'EFTPS Payment failed',
                    'api_response' => $response
                ],404);
            }
        $payment = EftpsPayment::create([
            'company_id' => $companyId,
            'tax_type' => $taxType,
            'amount' => $amount,
            'period' => $period,
            'submission_id' => $response['SubmissionId'],
            'status' => $response['Status'],
            'payment_mode' => 'EFTPS-Auto',
            'confirmation_number' => $response['ConfirmationNumber'] ?? null,
            'payment_date' => $response['PaymentDate'] ?? now(),
        ]);

        return response()->json([
            'status' => 'success',
            'payment' => $payment,
            'response' => $response
        ],200);
    }

    // Payment History
    public function history()
    {
        $companyId = auth()->user()->company_id;

        $payments = EftpsPayment::where('company_id', $companyId)
            ->orderByDesc('created_at')
            ->get();

        return response()->json(['status'=>'success','payments'=>$payments]);
    }

    public function companyReport()
    {
         $company_id = auth()->user()->company_id;
        $payments = EftpsPayment::where('company_id', $company_id)
            ->orderBy('period', 'desc')
            ->get();

        $summary = EftpsPayment::selectRaw('tax_type, SUM(amount) as total_amount, COUNT(*) as total_payments')
            ->where('company_id', $company_id)
            ->groupBy('tax_type')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => [
                'payments' => $payments,
                'summary' => $summary
            ]
        ]);
    }
}
