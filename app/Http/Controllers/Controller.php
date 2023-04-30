<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller as BaseController;

class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    /**
     * @param $data
     * @param string $message
     * @param string $status
     * @param int $statusCode
     * @return JsonResponse
     */
    public function apiResponse(
        $data,
        string $message = 'Success',
        string $status = 'success',
        int $statusCode = 200
    ): JsonResponse {
        $response = [
            'data' => $data,
            'message' => $message,
            'status' => $status
        ];
        return response()->json($response, $statusCode);
    }
}
