<?php

use Modules\EmployeeSalaryItems\Http\Controllers\EmployeeSalaryItemsController;

Route::middleware(['json.response'])->prefix('v1')->group(function () {
    Route::middleware(['auth:sanctum'])->group(function () {
        Route::post('associate-salary-items-with-employees', [EmployeeSalaryItemsController::class, 'store'])
            ->name('salary-items-associate-with-employees');
        Route::get('salary-items-value-against-category', [EmployeeSalaryItemsController::class, 'getSalaryItemsValue'])
            ->name('salary-items-value-against-category');
        Route::patch('salary-item-amount/{employee_salary_item}', [EmployeeSalaryItemsController::class, 'update_salary_item']);
        Route::delete('salary-item-amount/{employee_salary_item}', [EmployeeSalaryItemsController::class, 'delete_salary_item']);
    });
});
