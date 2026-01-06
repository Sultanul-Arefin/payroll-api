<?php

use Modules\Payslip\Http\Controllers\PayslipController;

Route::middleware(['json.response'])->prefix('v1')->group(function () {
    Route::middleware(['auth:sanctum'])->group(function () {
        Route::post('request-for-payslip', [PayslipController::class, 'request_for_payslip']);
        Route::post('leaves-data', [PayslipController::class, 'leaves_data']);
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
        Route::get('preview-uk-payslip/{payslip}', [PayslipController::class, 'preview_uk_payslip'])
            ->name('preview-uk-payslip');
        Route::get('preview-usa-payslip/{payslip}', [PayslipController::class, 'preview_usa_payslip'])
            ->name('preview-usa-payslip');
        Route::get('preview-us-payslip/{payslip}', [PayslipController::class, 'preview_us_payslip'])
            ->name('preview-us-payslip');
        Route::get('preview-indian-payslip/{payslip}', [PayslipController::class, 'preview_indian_payslip'])
            ->name('preview-indian-payslip');
        Route::get('preview-african-payslip/{payslip}', [PayslipController::class, 'preview_south_african_payslip'])
        ->name('preview-african-payslip');
        Route::get('preview-german-payslip/{payslip}', [PayslipController::class, 'preview_german_payslip'])
        ->name('preview-german-payslip');
        Route::get('preview-australian-payslip/{payslip}', [PayslipController::class, 'preview_australian_payslip'])
        ->name('preview-australian-payslip');
        Route::get('preview-portuguese-payslip/{payslip}', [PayslipController::class, 'preview_portuguese_payslip'])
        ->name('preview-portuguese-payslip');
        Route::get('preview-japanese-payslip/{payslip}', [PayslipController::class, 'preview_japanese_payslip'])
        ->name('preview-japanese-payslip');
        Route::get('preview-spain-payslip/{payslip}', [PayslipController::class, 'preview_spain_payslip'])
        ->name('preview-spain-payslip');
        Route::get('preview-swedish-payslip/{payslip}', [PayslipController::class, 'preview_swedish_payslip'])
        ->name('preview-swedish-payslip');
        Route::get('preview-turkey-payslip/{payslip}', [PayslipController::class, 'preview_turkey_payslip'])
        ->name('preview-turkey-payslip');
        Route::get('preview-uae-payslip/{payslip}', [PayslipController::class, 'preview_uae_payslip'])
        ->name('preview-uae-payslip');
        Route::get('payslips', [PayslipController::class, 'payslips'])
            ->name('payslips');
        Route::get('payslips/{payslip}', [PayslipController::class, 'show'])
            ->name('payslips.show');
        Route::delete('delete-payslip/{payslip}', [PayslipController::class, 'delete_payslip'])
            ->name('payslip.delete');
        Route::get('payslips-history', [PayslipController::class, 'payslips_history'])
            ->name('payslip.history');
        Route::get('payslips-employees-by-department', [PayslipController::class, 'employees_by_department'])
            ->name('payslips.employees.by.department');
        

        Route::post('upload-own-design-payslip', [PayslipController::class, 'upload_own_design_payslip']);
    });
});
