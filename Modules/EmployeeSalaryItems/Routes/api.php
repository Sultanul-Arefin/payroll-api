<?php

use Modules\EmployeeSalaryItems\Http\Controllers\EmployeeSalaryItemsController;

Route::middleware(['json.response'])->prefix('v1')->group(function () {
    Route::middleware(['auth:sanctum'])->group(function () {
        Route::post('associate-salary-items-with-employees', [EmployeeSalaryItemsController::class, 'store'])
            ->name('salary-items-associate-with-employees');
    });
});
