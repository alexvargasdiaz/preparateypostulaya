<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Historial\Http\Controllers\HistorialController;

Route::middleware(['auth', 'estudiante'])->group(function () {
    Route::get('/historial', [HistorialController::class, 'index'])->name('historial');
});
