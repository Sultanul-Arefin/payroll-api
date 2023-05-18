<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Modules\User\Http\Controllers\UserController;

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
        Route::get('user',[UserController::class, 'index'])->name('user_list');
        Route::post('user',[UserController::class, 'store'])->name('add_user');
        Route::post('user/{user}',[UserController::class, 'update'])->name('update_user');
    });
});