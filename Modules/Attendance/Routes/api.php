<?php

use Modules\Attendance\Http\Controllers\AttendanceController;
use Modules\Attendance\Http\Controllers\LeaveController;

Route::middleware(['json.response'])->prefix('v1')->group(function () {
    Route::middleware(['auth:sanctum'])->group(function () {
        Route::apiResource('attendance', AttendanceController::class);
        Route::get('requested-attendance', [AttendanceController::class, 'requested_attendance']);
        Route::post('approve-all-attendance-by-date', [AttendanceController::class, 'approve_all_attendance_by_date']);

        Route::get('leave-types', [LeaveController::class, 'leave_types']);
    });
});
