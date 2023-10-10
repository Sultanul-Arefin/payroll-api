<?php

use Illuminate\Http\Request;
use Modules\Notification\Http\Controllers\NotificationController;

Route::middleware(['json.response'])->prefix('v1')->group(function(){
    Route::middleware(['auth:sanctum'])->group(function(){

        Route::get('notifications', [NotificationController::class, 'index']);

    });
});
