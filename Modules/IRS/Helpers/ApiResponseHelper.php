<?php

namespace Modules\IRS\Helpers;

class ApiResponseHelper
{
    /**
     * Smooth TaxBandits API Error Response
     *
     * @param array|\Illuminate\Http\Client\Response $response
     * @return array
     */
    public static function formatErrorResponse($response)
    {
        
        $body = is_array($response) ? $response : (method_exists($response, 'json') ? $response->json() : []);

        // ------------------------------
        // Case 1: Nested errors.Errors
        // ------------------------------
        if (isset($body['errors']['Errors']) && is_array($body['errors']['Errors'])) {
            $errors = $body['errors']['Errors'];
            $messages = [];
            foreach ($errors as $err) {
                $messages[] = $err['Message'] ?? 'Unknown error';
            }

            return [
                'status' => 'error',
                'http_status' => $body['http_status'] ?? 400,
                'message' => implode(' | ', $messages),
                'code' => $errors[0]['Id'] ?? null,
            ];
        }

        // ------------------------------
        // Case 2: Top-level Errors array
        // ------------------------------
        if (isset($body['Errors']) && is_array($body['Errors'])) {
            $errors = $body['Errors'];
            $messages = [];
            foreach ($errors as $err) {
                $messages[] = $err['Message'] ?? 'Unknown error';
            }

            return [
                'status' => 'error',
                'http_status' => $body['http_status'] ?? 400,
                'message' => implode(' | ', $messages),
                'code' => $errors[0]['Id'] ?? null,
            ];
        }

        // ------------------------------
        // Case 3: StatusMessage fallback
        // ------------------------------
        if (isset($body['StatusMessage'])) {
            return [
                'status' => 'error',
                'http_status' => $body['StatusCode'] ?? 400,
                'message' => $body['StatusMessage'],
                'code' => $body['StatusCode'] ?? null,
            ];
        }

        // ------------------------------
        // Case 4: Generic fallback
        // ------------------------------
        return [
            'status' => 'error',
            'http_status' => $body['http_status'] ?? 400,
            'message' => $body['message'] ?? 'Something went wrong',
            'code' => $body['code'] ?? null,
        ];
    }
}
