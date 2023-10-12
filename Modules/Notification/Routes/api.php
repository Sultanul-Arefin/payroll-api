<?php

use Illuminate\Http\Request;
use Modules\Notification\Http\Controllers\NotificationController;

Route::middleware(['json.response'])->prefix('v1')->group(function(){
    Route::middleware(['auth:sanctum'])->group(function(){

        Route::get('notifications', [NotificationController::class, 'all_notifications'])->name('notifcations.all_notifications');
        Route::get('notifications/un-read', [NotificationController::class, 'un_read_notifications'])->name('notifcations.un_read_notifications');
        Route::get('notifications/mark/read', [NotificationController::class, 'mark_all_as_read'])->name('notifcations.mark_all_as_read');
        Route::get('notifications/{id}/mark/read', [NotificationController::class, 'mark_single_as_read'])->name('notifcations.mark_single_as_read');

    });
});
