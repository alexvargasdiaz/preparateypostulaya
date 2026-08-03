<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Bitacora\Http\Controllers\BitacoraController;

// ─── Admin: bitácora de procesos ────────────────────────────────
Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/bitacora', [BitacoraController::class, 'index'])->name('bitacora');
    Route::get('/bitacora/exportar/excel', [BitacoraController::class, 'exportarExcel'])->name('bitacora.exportar-excel');
    Route::get('/bitacora/exportar/pdf', [BitacoraController::class, 'exportarPdf'])->name('bitacora.exportar-pdf');
});
