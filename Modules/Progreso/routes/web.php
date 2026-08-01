<?php

use Illuminate\Support\Facades\Route;
use Modules\Progreso\Http\Controllers\ProgresoController;

/*
|--------------------------------------------------------------------------
| Web Routes — Módulo Progreso
|--------------------------------------------------------------------------
|
| Rutas de la página de evolución y progreso del usuario.
|
*/

Route::middleware(['auth', 'estudiante'])->group(function () {
    Route::get('/progreso', [ProgresoController::class, 'index'])->name('progreso');
});
