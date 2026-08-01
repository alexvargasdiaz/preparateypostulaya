<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Examenes\Http\Controllers\ExplorarController;
use Modules\Examenes\Http\Controllers\RendicionController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::get('/examenes', [ExplorarController::class, 'index'])->name('api.examenes.index');
    Route::post('/examenes/{examen}/iniciar', [RendicionController::class, 'iniciar'])->name('api.examenes.iniciar');
});
