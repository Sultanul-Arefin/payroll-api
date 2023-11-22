<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware(['json.response'])->prefix('v1')->group(function () {
    Route::middleware(['auth:sanctum'])->group(function () {
        Route::post('add-leave-request', []);
        Route::get('all-leave-request', []);
        Route::get('time-management-report', []);
        Route::get('attendance-report', []);
        Route::post('add-overtime-doubleOvertime-bonus', []);
        Route::get('attendance-calendar-overview-per-person', []);
    });
});