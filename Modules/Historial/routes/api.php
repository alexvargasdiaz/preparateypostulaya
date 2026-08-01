<?php

use Illuminate\Support\Facades\Route;
use Modules\Historial\Http\Controllers\HistorialController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('historials', HistorialController::class)->names('historial');
});
