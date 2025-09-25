<?php

namespace App\Http\Traits;

use App\Models\StaffTask;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Resources\StaffTaskDetailsResource;

trait StaffTaskTrait
{
    public function details(StaffTask $staff_task)
    {
        return apiResponse(
            data: new StaffTaskDetailsResource($staff_task)
        );
    }

    public function update_details(StaffTask $staff_task, Request $request)
    {
        $request->validate([
            'title' => 'required',
            'given_date' => 'required',
            'start_date' => 'required',
            'end_date' => 'required',
        ]);
        DB::transaction(function () use ($request, $staff_task) {
            // 1. Delete old main points & sub points (cascade handles sub points)
            $staff_task->main_points()->delete();

            // 2. Insert fresh main points & sub points
            foreach ($request->main_points as $mpData) {
                $mainPoint = $staff_task->main_points()->create([
                    'title'       => $mpData['title'],
                    'manager_id'  => $mpData['manager_id'],
                    'assigned_to' => $mpData['assigned_to'],
                    'given_date'  => $mpData['given_date'],
                    'start_date'  => $mpData['start_date'],
                    'end_date'    => $mpData['end_date'],
                    'progress'    => $mpData['progress'],
                    'documents'   => $mpData['documents'] ?? null,
                    'estimated_time' => $mpData['estimated_time'] ?? null,
                ]);

                foreach ($mpData['sub_points'] as $spData) {
                    $mainPoint->sub_points()->create([
                        'title'       => $spData['title'],
                        'manager_id'  => $spData['manager_id'],
                        'assigned_to' => $spData['assigned_to'],
                        'given_date'  => $spData['given_date'],
                        'start_date'  => $spData['start_date'],
                        'end_date'    => $spData['end_date'],
                        'progress'    => $spData['progress'],
                        'documents'   => $spData['documents'] ?? null,
                        'estimated_time' => $spData['estimated_time'] ?? null,
                    ]);
                }
            }
        });

        return $this->apiResponse(
            data: null,
            message: 'Task updated successfully'
        );
    }
}