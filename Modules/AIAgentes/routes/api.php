<?php

use Illuminate\Support\Facades\Route;
use Modules\AIAgentes\Http\Controllers\AIAgentesController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('aiagentes', AIAgentesController::class)->names('aiagentes');
});
