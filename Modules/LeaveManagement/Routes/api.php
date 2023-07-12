<?php

use Modules\LeaveManagement\Http\Controllers\LeaveManagementController;

Route::middleware(['json.response'])->prefix('v1')->group(function(){
    Route::middleware(['auth:sanctum'])->group(function(){

        Route::get('leave-types', [LeaveManagementController::class, 'leave_types']);
    });
});