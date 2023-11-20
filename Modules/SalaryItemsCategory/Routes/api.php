<?php

use Modules\SalaryItemsCategory\Http\Controllers\SalaryItemsCategoryController;

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
        Route::get('salary-items-categories', [SalaryItemsCategoryController::class, 'index'])
            ->name('salary-items-category');
        Route::get('salary-items-value-against-category', [SalaryItemsCategoryController::class, 'getSalaryItemsValue'])
            ->name('salary-items-value-against-category');
    });
});
