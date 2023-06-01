<?php

use Illuminate\Http\Request;
use Modules\ProjectManagement\Http\Controllers\ProjectColumnController;
use Modules\ProjectManagement\Http\Controllers\ProjectController;
use Modules\ProjectManagement\Http\Controllers\ProjectManagementController;
use Modules\ProjectManagement\Http\Controllers\TaskController;

Route::middleware(['json.response'])->prefix('v1')->group(function(){
    Route::middleware(['auth:sanctum'])->group(function(){
        Route::apiResource('projects', ProjectController::class);

        Route::apiResource('project_columns', ProjectColumnController::class);

        Route::get('task/{project}', [TaskController::class, 'index']);
        Route::post('task', [TaskController::class, 'store']);
    });
});