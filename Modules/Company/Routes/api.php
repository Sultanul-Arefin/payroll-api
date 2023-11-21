<?php

use Illuminate\Support\Facades\Route;
use Modules\Company\Http\Controllers\CompanyController;

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
        // Route::middleware('checkIfCompanyCreated')->group(function(){
        Route::get('company', [CompanyController::class, 'index'])
            ->name('details_company');
        // });
        // Route::post('company', [CompanyController::class, 'store'])
        //     ->name('add_company');
        Route::post('company/{company}', [CompanyController::class, 'update'])
            ->name('update_company');

        // ADD OR UPDATE WEEKLY HOLIDAYS
        Route::post('add-weekly-holidays', [CompanyController::class, 'add_weekly_holidays'])
            ->name('add-weekly-holidays');

        // ADD OR UPDATE ANNUAL HOLIDAYS
        Route::post('add-annual-holidays', [CompanyController::class, 'add_annual_holidays'])
            ->name('add-annual-holidays');
    });
});
