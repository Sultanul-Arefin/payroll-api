<?php

use Illuminate\Http\Request;
use Modules\Attendance\Http\Controllers\AttendanceController;

Route::middleware(['json.response'])->prefix('v1')->group(function(){
    Route::middleware(['auth:sanctum'])->group(function(){
        Route::apiResource('attendance', AttendanceController::class);
    });
});