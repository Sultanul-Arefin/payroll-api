<?php

use Modules\Dashboard\Http\Controllers\DashboardController;

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
        });

    });
});
