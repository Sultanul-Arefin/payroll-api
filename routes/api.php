<?php

use App\Http\Controllers\SupportTicketController;
use Illuminate\Support\Facades\Route;
use Modules\Auth\Http\Controllers\RegisteredUserController;
use App\Http\Controllers\StaffObjectiveController;

Route::middleware(['json.response'])->prefix('v1')->group(function () {
    Route::middleware(['auth:sanctum'])->group(function () {
        Route::get('support-tickets', [SupportTicketController::class, 'index']);
        Route::post('support-tickets', [SupportTicketController::class, 'store']);
        Route::get('help-article-category', [SupportTicketController::class, 'help_article_category']);
        Route::get('help-articles', [SupportTicketController::class, 'help_articles']);
        Route::apiResource('staff-objective', StaffObjectiveController::class);

        // DUE TO SOME ISSUES, REPORT SECTION IS WRITTEN HERE. WILL BE SHIFT SOON
        // DIGITAL TAX REPORT

        // DIGITAL SOCIAL REPORT

        // REPORT
        Route::get('department-wise-report', [ReportController::class, 'department_wise_report'])
            ->name('department-wise-report');
        Route::get('internal-report', [ReportController::class, 'get_internal_report'])
            ->name('internal-report');
    });
    Route::post('registration-from-bfin-technology', [RegisteredUserController::class, 'registration_from_bfin_technology']);
});
