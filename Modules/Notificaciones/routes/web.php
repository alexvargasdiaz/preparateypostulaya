<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Notificaciones\Http\Controllers\NotificacionController;

Route::middleware(['auth'])->group(function () {
    // Página principal de notificaciones
    Route::get('/notificaciones', [NotificacionController::class, 'index'])->name('notificaciones.index');

    // API endpoints (para el dropdown y acciones AJAX)
    Route::get('/api/notificaciones/recientes', [NotificacionController::class, 'obtenerRecientes'])->name('notificaciones.api.recientes');
    Route::post('/notificaciones/{id}/leer', [NotificacionController::class, 'marcarLeida'])->name('notificaciones.leer');
    Route::post('/notificaciones/leer-todas', [NotificacionController::class, 'marcarTodasLeidas'])->name('notificaciones.leer-todas');

    // Preferencias
    Route::post('/notificaciones/preferencias', [NotificacionController::class, 'actualizarPreferencias'])->name('notificaciones.preferencias');
});
