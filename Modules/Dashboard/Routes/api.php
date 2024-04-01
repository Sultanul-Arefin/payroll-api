<?php

use Modules\Dashboard\Http\Controllers\DashboardController;
use Modules\Dashboard\Http\Controllers\ReportController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware(['json.response'])->prefix('v1')->group(function () {
    Route::middleware(['auth:sanctum'])->group(function () {

        Route::prefix('/dashboard')->group(function () {
            Route::get('/total/employee', [DashboardController::class, 'totalEmployee']);
            Route::get('/total/department', [DashboardController::class, 'totalDepartment']);
            Route::get('/holiday', [DashboardController::class, 'holiday']);
            Route::get('total-paid-salary', [DashboardController::class, 'total_paid_salary']);
            Route::get('total-staff-cost', [DashboardController::class, 'total_staff_cost']);
            Route::get('last-12-months-data', [DashboardController::class, 'last_12_months_data']);
            Route::get('recent-leaves', [DashboardController::class, 'get_recent_leaves']);
            Route::get('others', [DashboardController::class, 'get_others']);
        });

        // DUE TO SOME ISSUES, REPORT SECTION IS WRITTEN HERE. WILL BE SHIFT SOON
        Route::get('department-wise-report', [ReportController::class, 'department_wise_report'])
            ->name('department-wise-report');
        Route::get('internal-report', [ReportController::class, 'get_internal_report'])
            ->name('internal-report');

    });
});
