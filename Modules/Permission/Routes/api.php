<?php

use Modules\Permission\Http\Controllers\PermissionController;

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
Route::middleware(['auth:sanctum', 'json.response'])
    ->prefix('v1')
    ->group(function () {
        Route::get('/permissions/users/{user?}', [PermissionController::class, 'userPermissions'])
            ->name('permissions.user.index');
    });
