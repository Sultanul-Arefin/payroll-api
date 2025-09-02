<?php
namespace Modules\IRS\Http\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TaxBanditsService
{
    protected $apiUrl;
    protected $clientId;
    protected $clientSecret;
    protected $userToken;

    public function __construct()
    {
        $this->apiUrl = config('services.taxbandits.api_url'); 
        $this->clientId = config('services.taxbandits.client_id');
        $this->clientSecret = config('services.taxbandits.client_secret');
        $this->userToken = config('services.taxbandits.user_token');
    }

    private function generateJWT()
    {
        $header = ['alg' => 'HS256', 'typ' => 'JWT'];
        $payload = [
            'iss' => $this->clientId,
            'sub' => $this->clientId,
            'aud' => $this->userToken,
            'iat' => time(),
            'exp' => time() + 300,
        ];

        $headerEncoded = $this->base64UrlEncode(json_encode($header));
        $payloadEncoded = $this->base64UrlEncode(json_encode($payload));
        $signature = hash_hmac('sha256', $headerEncoded . '.' . $payloadEncoded, $this->clientSecret, true);
        $signatureEncoded = $this->base64UrlEncode($signature);

        return $headerEncoded . '.' . $payloadEncoded . '.' . $signatureEncoded;
    }

    private function base64UrlEncode($data)
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    public function createEftpsPayment($companyId, $taxType, $amount, $period, $confirmation_number)
    {
        $token = $this->generateJWT();

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $token,
                'Accept' => 'application/json',
            ])->post("{$this->apiUrl}/EFTPS/payment", [
                'CompanyId' => $companyId,
                'TaxType'   => $taxType,
                'Amount'    => $amount,
                'Period'    => $period,
                'confirmation_number'=> $confirmation_number
            ]);

            Log::info('EFTPS Raw Response:', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return $response->json();

        } catch (\Exception $e) {
            Log::error('EFTPS Payment Error: ' . $e->getMessage());
            return ['Status' => 'Error', 'Message' => $e->getMessage()];
        }
    }
}
