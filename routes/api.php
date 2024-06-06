<?php

use App\Http\Controllers\SupportTicketController;
use App\Models\User;
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
    });
    Route::post('registration-from-bfin-technology', [RegisteredUserController::class, 'registration_from_bfin_technology']);
});
