<?php

use Illuminate\Http\Request;
use Modules\Payslip\Http\Controllers\PayslipController;

Route::middleware(['json.response'])->prefix('v1')->group(function () {
    Route::middleware(['auth:sanctum'])->group(function () {
        Route::post('request-for-payslip', [PayslipController::class, 'request_for_payslip']);
        Route::get('employee-salary-items', [PayslipController::class, 'employee_salary_items']);
        Route::post('employee-salary-items-calculation', [PayslipController::class, 'employee_salary_items_calculation']);
        Route::post('employee-salary-information', [PayslipController::class, 'salary_information_before_running_payslip'])
            ->name('salary-information-before-running-payslip');
        Route::post('run-payslip', [PayslipController::class, 'run_payslip'])
            ->name('run-payslip');
        Route::get('preview-payslip', [PayslipController::class, 'preview_payslip'])
            ->name('preview-payslip');
        Route::get('payslips', [PayslipController::class, 'payslips'])
            ->name('payslips');
    });
});