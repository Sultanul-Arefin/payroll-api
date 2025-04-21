<?php

use Modules\Attendance\Http\Controllers\AttendanceController;
use Modules\Attendance\Http\Controllers\LeaveController;

Route::middleware(['json.response'])->prefix('v1')->group(function () {
    Route::middleware(['auth:sanctum'])->group(function () {
        Route::apiResource('attendance', AttendanceController::class);
        Route::post('update-attendance-details/{attendance_detail}', [AttendanceController::class, 'update_attendance_details']);
        Route::post('user-data-for-manual-attendance-by-admin', [AttendanceController::class, 'manual_attendance_by_admin']);
        Route::post('get-user-data-for-manual-attendance-by-admin', [AttendanceController::class, 'get_user_data_for_manual_attendance']);
        Route::get('requested-attendance', [AttendanceController::class, 'requested_attendance']);
        Route::post('approve-all-attendance-by-date', [AttendanceController::class, 'approve_all_attendance_by_date']);
        Route::post('approve-individual-attendance/{attendance}', [AttendanceController::class, 'approve_individual_attendance']);

        Route::get('leave-types', [LeaveController::class, 'leave_types']);
    });
});
