<?php

use Illuminate\Http\JsonResponse;

if (! function_exists('apiResponse')) {

    function apiResponse(
        $data,
        string $message = 'Success',
        string $status = 'success',
        int $statusCode = 200
    ): JsonResponse {
        $response = [
            'data' => $data,
            'message' => $message,
            'status' => $status,
        ];

        return response()->json($response, $statusCode);
    }
}

if (! function_exists('default_project_columns')) {
    function default_project_columns()
    {
        return [
            'Backlog',
            'In Progress',
        ];
    }
}
