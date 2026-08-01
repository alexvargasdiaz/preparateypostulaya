<?php

use Illuminate\Support\Facades\Route;
use Modules\Resultados\Http\Controllers\ResultadosController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('resultados', ResultadosController::class)->names('resultados');
});
