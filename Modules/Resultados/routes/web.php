<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Resultados\Http\Controllers\ResultadosController;

Route::middleware(['auth'])->group(function () {
    Route::get('/resultados/{id}', [ResultadosController::class, 'show'])->name('resultados.show');
    Route::post('/resultados/{id}/enviar-email', [ResultadosController::class, 'enviarEmail'])->name('resultados.enviar-email');
    Route::post('/resultados/{id}/whatsapp', [ResultadosController::class, 'generarWhatsAppLink'])->name('resultados.whatsapp');
});
