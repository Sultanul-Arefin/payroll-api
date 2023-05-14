<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Modules\Employee\Http\Controllers\EmployeeController;

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

Route::middleware(['json.response'])->prefix('v1')->group(function(){
    Route::middleware(['auth:sanctum'])->group(function(){
        Route::post('employee',[EmployeeController::class, 'store'])->name('add_employee');
    });
});