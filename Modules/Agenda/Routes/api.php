<?php

use Illuminate\Http\Request;
use Modules\Agenda\Http\Controllers\AgendaController;

Route::middleware(['json.response'])->prefix('v1')->group(function(){
    Route::middleware(['auth:sanctum'])->group(function(){
        Route::get('agenda', [AgendaController::class, 'index']);
        Route::post('agenda', [AgendaController::class, 'store']);
        Route::get('calendar-agenda', [AgendaController::class, 'calendar_agenda']);

        Route::get('agenda/{agenda}', [AgendaController::class, 'show']);
        Route::post('agenda/{agenda}', [AgendaController::class, 'update']);
        Route::delete('agenda/{agenda}', [AgendaController::class, 'destroy']);
    });
});