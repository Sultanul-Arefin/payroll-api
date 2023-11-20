<?php

use Modules\Package\Http\Controllers\PackageController;

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
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('packages', [PackageController::class, 'index'])
            ->name('package_list');
    });
    Route::get('package-list', [PackageController::class, 'package_list'])
        ->name('package_list_without_guard');
});
