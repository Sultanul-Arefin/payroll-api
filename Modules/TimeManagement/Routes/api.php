<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Modules\TimeManagement\Http\Controllers\TimeManagementController;

Route::middleware(['json.response'])->prefix('v1')->group(function () {
    Route::middleware(['auth:sanctum'])->group(function () {
        Route::get('time-management-report', [TimeManagementController::class, 'time_management_report']);
        Route::get('attendance-report', [TimeManagementController::class, 'attendance_report']);
        Route::post('add-overtime-doubleOvertime-bonus', []);
        Route::get('attendance-calendar-overview-per-person', [TimeManagementController::class, 'attendance_calendar_overview_per_person']);
    });
});