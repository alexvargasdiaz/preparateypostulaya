<?php

use Illuminate\Support\Facades\Route;
use Modules\Progreso\Http\Controllers\ProgresoController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('progresos', ProgresoController::class)->names('progreso');
});
