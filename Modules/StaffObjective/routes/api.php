<?php

use Illuminate\Support\Facades\Route;
use Modules\StaffObjective\Http\Controllers\StaffObjectiveController;

/*
 *--------------------------------------------------------------------------
 * API Routes
 *--------------------------------------------------------------------------
 *
 * Here is where you can register API routes for your application. These
 * routes are loaded by the RouteServiceProvider within a group which
 * is assigned the "api" middleware group. Enjoy building your API!
 *
*/

Route::middleware(['json.response'])->prefix('v1')->group(function () {
    Route::middleware(['auth:sanctum'])->group(function () {
        Route::apiResource('staff-objective', StaffObjectiveController::class)->names('staff.objective');
    });
});
