<?php

use Modules\Designation\Http\Controllers\DesignationController;

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
        Route::get('designation', [DesignationController::class, 'index'])->name('designation_list');
        Route::get('designation/{designation}', [DesignationController::class, 'show'])->name('designation_show');
        Route::post('designation', [DesignationController::class, 'store'])->name('add_designation');
        Route::post('designation/{designation}', [DesignationController::class, 'update'])->name('update_designation');
        Route::delete('designation/destroy/{designation}', [DesignationController::class, 'destroy']);
    });
});
