<?php

use Modules\Payslip\Http\Controllers\PayslipController;

Route::middleware(['json.response'])->prefix('v1')->group(function () {
    Route::middleware(['auth:sanctum'])->group(function () {
        Route::post('request-for-payslip', [PayslipController::class, 'request_for_payslip']);
        Route::get('employee-salary-items', [PayslipController::class, 'employee_salary_items']);
        Route::get('employee-salary-items-calculation', [PayslipController::class, 'employee_salary_items_calculation']);
        Route::post('run-payslip', [PayslipController::class, 'run_payslip'])
            ->name('run-payslip');
        Route::post('run-department-wise-payslip', [PayslipController::class, 'run_department_wise_payslip'])
            ->name('run-department-wise-payslip');
        Route::get('preview-payslip/{payslip}', [PayslipController::class, 'preview_payslip'])
            ->name('preview-payslip');
        Route::get('preview-french-payslip/{payslip}', [PayslipController::class, 'preview_french_payslip'])
            ->name('preview-french-payslip');
        Route::get('payslips', [PayslipController::class, 'payslips'])
            ->name('payslips');
        Route::delete('delete-payslip/{payslip}', [PayslipController::class, 'delete_payslip'])
            ->name('payslip.delete');
    });
});
