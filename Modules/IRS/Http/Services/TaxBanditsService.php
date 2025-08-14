<?php

namespace Modules\IRS\Http\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TaxBanditsService
{
    public function submitForm941(array $payload)
    {
        $baseUrl = config('services.taxbandits.base_url');
        $apiKey = config('services.taxbandits.api_key');
        $secretKey = config('services.taxbandits.secret_key');

        $endpoint = rtrim($baseUrl, '/') . '/Form941/RequestSubmission';

        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
            ])->post($endpoint, [
                'UserAuthInfo' => [
                    'UserName'   => $apiKey,
                    'Password'   => $secretKey,
                ],
                'Form941Records' => [$payload], // NOTE: Must be array of records
            ]);

            $status = $response->status();
            $body = $response->json();

            if ($response->successful()) {
                return [
                    'status' => 'success',
                    'message' => 'Form 941 submitted to TaxBandits successfully',
                    'http_status' => $status,
                    'response' => $body,
                ];
            } else {
                return [
                    'status' => 'error',
                    'message' => $body['message'] ?? 'Unknown error occurred',
                    'http_status' => $status,
                    'response' => $body,
                ];
            }
        } catch (\Exception $e) {
            Log::error('TaxBandits API Exception: ' . $e->getMessage());

            return [
                'status' => 'error',
                'message' => 'Exception occurred: ' . $e->getMessage(),
                'response' => [],
            ];
        }
    }
}