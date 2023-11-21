<?php

use Modules\SalaryItemsName\Http\Controllers\SalaryItemsNameController;

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
        Route::get('salary-items/{salary_items_category_id}', [SalaryItemsNameController::class, 'index'])
            ->name('salary-items-name');
        Route::post('salary-items', [SalaryItemsNameController::class, 'store'])
            ->name('salary-items-name-store');
    });
});
