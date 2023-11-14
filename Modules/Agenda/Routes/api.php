<?php

use Illuminate\Http\Request;
use Modules\Agenda\Http\Controllers\AgendaController;

Route::middleware(['json.response'])->prefix('v1')->group(function(){
    Route::middleware(['auth:sanctum'])->group(function(){
        Route::get('agenda', [AgendaController::class, 'index']);
        Route::post('agenda', [AgendaController::class, 'store']);
    });
});