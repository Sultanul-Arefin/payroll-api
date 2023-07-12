<?php

use Illuminate\Http\Request;
use Modules\Department\Http\Controllers\DepartmentController;

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
    Route::middleware(['auth:sanctum', 'checkIfCompanyCreated'])->group(function () {
        Route::get('departments', [DepartmentController::class, 'index'])
            ->name('details_department');
        Route::get('all-departments', [DepartmentController::class, 'all_departments'])
            ->name('all_departments');
        Route::post('department', [DepartmentController::class, 'store'])
            ->name('add_department');
        Route::post('department/{department}', [DepartmentController::class, 'update'])
            ->name('update_department');
    });
});
