<?php

use Illuminate\Http\Request;
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
    Route::middleware(['auth:sanctum'])->group(function(){

        Route::prefix('/dashboard')->group(function(){
            Route::get('/total/employee', [DashboardController::class, 'totalEmployee']);
        });


    });
});
