<?php

use Illuminate\Http\Request;
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
        Route::middleware('checkIfCompanyCreated')->group(function(){
            Route::get('company', [CompanyController::class, 'index'])
                ->name('details_company');
        });
        Route::post('company', [CompanyController::class, 'store'])
            ->name('add_company');
        Route::patch('company/{company}', [CompanyController::class, 'update'])
            ->name('update_company');
    });
});
