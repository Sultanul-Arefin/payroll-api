<?php

use App\Http\Controllers\HelpArticleController;
use App\Http\Controllers\SupportTicketController;
use Illuminate\Support\Facades\Route;
use Modules\Auth\Http\Controllers\RegisteredUserController;
use App\Http\Controllers\StaffObjectiveController;
use App\Http\Controllers\SiteSettingsController;
use App\Http\Controllers\YoutubeVideoController;
use Modules\Dashboard\Http\Controllers\ReportController;

Route::middleware(['json.response'])->prefix('v1')->group(function () {
    Route::middleware(['auth:sanctum'])->group(function () {
        // SUPPORT TICKET OPTION NEED TO BE SHOWED
        Route::get('support-tickets', [SupportTicketController::class, 'index']);
        Route::post('support-tickets', [SupportTicketController::class, 'store']);
        Route::get('support-ticket/{support_ticket}', [SupportTicketController::class, 'show']);
        Route::get('help-article-category', [SupportTicketController::class, 'help_article_category']);
        Route::get('help-articles', [SupportTicketController::class, 'help_articles']);
        Route::apiResource('staff-objective', StaffObjectiveController::class);

        // DUE TO SOME ISSUES, REPORT SECTION IS WRITTEN HERE. WILL BE SHIFT SOON. REPORT SECTION WILL REMAIN HERE UNTIL MODULE ISSUE FIXED
        // DIGITAL TAX REPORT // THIS SECTION WILL BE SHIFT TO THE REPORT SECTION
        Route::post('create-digital-tax-report', [ReportController::class, 'create_digital_tax_report']);
        Route::post('send-digital-tax-report', [ReportController::class, 'send_digital_tax_report']);
        Route::post('upload-dedicated-digital-tax-report', [ReportController::class, 'upload_dedicated_digital_tax_report']);

        // DIGITAL SOCIAL REPORT // THIS SECTION WILL BE SHIFT TO THE REPORT SECTION
        Route::post('create-digital-social-report', [ReportController::class, 'create_digital_social_report']);
        Route::post('send-digital-social-report', [ReportController::class, 'send_digital_social_report']);
        Route::post('upload-dedicated-digital-social-report', [ReportController::class, 'upload_dedicated_digital_social_report']);

        // REPORT // THIS SECTION WILL BE SHIFT TO THE REPORT SECTION
        Route::get('department-wise-report', [ReportController::class, 'department_wise_report'])
            ->name('department-wise-report');
        Route::get('internal-report', [ReportController::class, 'get_internal_report'])
            ->name('internal-report');

        // HELP ARTICLE SECTION
        Route::get('help-article-category', [HelpArticleController::class, 'help_article_category']);
        Route::get('help-article/{help_article}', [HelpArticleController::class, 'help_article']);

    });

    // THIS ROUTE IS FOR PREVIOUS BUY NOW PAGE WHERE WE'LL HIT THIS ROUTE WHEN BUYING
    Route::post('registration-from-bfin-technology', [RegisteredUserController::class, 'registration_from_bfin_technology']);

    // SITE SETTINGS
    Route::get('site-settings', [SiteSettingsController::class, 'index']);

    // YOUTUBE VIDEOS
    Route::get('youtube-videos', [YoutubeVideoController::class, 'index']);
});
