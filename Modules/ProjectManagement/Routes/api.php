<?php

use Modules\ProjectManagement\Http\Controllers\ProjectAssociatedColumnController;
use Modules\ProjectManagement\Http\Controllers\ProjectController;
use Modules\ProjectManagement\Http\Controllers\TaskController;

Route::middleware(['json.response'])->prefix('v1')->group(function () {
    Route::middleware(['auth:sanctum'])->group(function () {
        Route::apiResource('projects', ProjectController::class);

        Route::post('update-status/{project}', [ProjectController::class, 'update_status']);

        Route::post('project-column', [ProjectAssociatedColumnController::class, 'store']);
        Route::post('update-project-column/{project_associated_column}', [ProjectAssociatedColumnController::class, 'update_project_associated_column']);
        Route::post('change-column-index', [ProjectAssociatedColumnController::class, 'change_column_index']);

        Route::get('task/{project}', [TaskController::class, 'index']);
        Route::post('task', [TaskController::class, 'store']);
        Route::post('update-task/{task}', [TaskController::class, 'update']);
        Route::post('add-comment/{task}', [TaskController::class, 'add_comment']);
        Route::post('change-task-column', [TaskController::class, 'change_task_column']);
        Route::post('update-task-status/{task}', [TaskController::class, 'update_task_status']);

        Route::get('project-overview/{project}', [ProjectController::class, 'project_overview']);
    });
});
