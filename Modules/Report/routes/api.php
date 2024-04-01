<?php

use Illuminate\Support\Facades\Route;
use Modules\Report\Http\Controllers\ReportController;

Route::middleware(['json.response'])->prefix('v1')->group(function () {
    Route::middleware(['auth:sanctum'])->group(function () {
        Route::get('department-wise-report', [ReportController::class, 'department_wise_report'])
            ->name('department-wise-report');
        Route::get('internal-report', [ReportController::class, 'get_internal_report'])
            ->name('internal-report');
    });
});
