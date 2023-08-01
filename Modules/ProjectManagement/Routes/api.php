<?php

use Illuminate\Http\Request;
use Modules\ProjectManagement\Http\Controllers\ProjectAssociatedColumnController;
use Modules\ProjectManagement\Http\Controllers\ProjectColumnController;
use Modules\ProjectManagement\Http\Controllers\ProjectController;
use Modules\ProjectManagement\Http\Controllers\ProjectManagementController;
use Modules\ProjectManagement\Http\Controllers\TaskController;

Route::middleware(['json.response'])->prefix('v1')->group(function(){
    Route::middleware(['auth:sanctum'])->group(function(){
        Route::apiResource('projects', ProjectController::class);

        Route::post('project-column', [ProjectAssociatedColumnController::class, 'store']);
        Route::post('change-column-index', [ProjectAssociatedColumnController::class, 'change_column_index']);

        Route::get('task/{project}', [TaskController::class, 'index']);
        Route::post('task', [TaskController::class, 'store']);
        Route::post('update-task/{task}', [TaskController::class, 'update']);
        Route::post('change-task-column', [TaskController::class, 'change_task_column']);
    });
});