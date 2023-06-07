<?php

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Modules\Company\Entities\Company;
use Modules\ProjectManagement\Entities\ProjectColumn;

if (!function_exists('apiResponse')) {
    /**
     * @param $data
     * @param string $message
     * @param string $status
     * @param int $statusCode
     * @return JsonResponse
     */
    function apiResponse(
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

if(!function_exists('project_columns_seeder')){
    function project_columns_seeder(
        User $user,
        Company $company
    )
    {
        $columns = [
            'Backlog',
            'In Progress',
            'Completed',
            'On Hold',
            'Cancelled'
        ];
        foreach($columns as $value){
            ProjectColumn::create([
                'column_name' => $value,
                'company_id' => $company->id,
                'created_by' => $user->id
            ]);
        }
    }
}

if(!function_exists('default_project_columns')){
    function default_project_columns(){
        return [
            'Backlog',
            'In Progress',
            'Completed',
            'On Hold',
            'Cancelled'
        ];
    }
}