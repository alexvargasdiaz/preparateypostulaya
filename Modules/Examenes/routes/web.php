<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Examenes\Http\Controllers\ExplorarController;
use Modules\Examenes\Http\Controllers\RendicionController;
use Modules\Examenes\Http\Controllers\DiagnosticoController;
use Modules\Examenes\Http\Controllers\AreaAcademicaController;
use Modules\Examenes\Http\Controllers\AdminRequisitosCarreraController;
use Modules\Examenes\Http\Controllers\AdminDiagnosticoController;

// ─── Exploración de exámenes (pública) ───────────────────────
Route::get('/examenes', [ExplorarController::class, 'index'])->name('examenes.index');
Route::get('/examenes/universidades', [ExplorarController::class, 'universidades'])->name('examenes.universidades');

// ─── Rutas protegidas (solo estudiantes) ───────────────────────
Route::middleware(['auth', 'estudiante'])->group(function () {
    Route::prefix('examenes')->name('examenes.')->group(function () {
        Route::get('/iniciar', [RendicionController::class, 'iniciar'])->name('iniciar');
        Route::get('/intento/{intento}', [RendicionController::class, 'rendir'])->name('rendir');
        Route::post('/intento/{intento}/guardar', [RendicionController::class, 'guardarRespuesta'])->name('guardar');
        Route::post('/intento/{intento}/guardar-masivo', [RendicionController::class, 'guardarMasivo'])->name('guardar-masivo');
        Route::post('/intento/{intento}/finalizar', [RendicionController::class, 'finalizar'])->name('finalizar');

        // ─── Áreas académicas ──────────────────────────────────
        Route::get('/areas-academicas', [AreaAcademicaController::class, 'index'])->name('areas');
        Route::get('/areas-academicas/{areaId}/tipos', [AreaAcademicaController::class, 'tipos'])->name('area-tipos');
        Route::post('/areas-academicas/{areaId}/tipos/{tipoId}/iniciar', [AreaAcademicaController::class, 'iniciar'])->name('area-tipo-iniciar');
        Route::post('/areas-academicas/{areaId}/examenes/{examenId}/iniciar', [AreaAcademicaController::class, 'iniciarExamen'])->name('area-examen-iniciar');

        // ─── Simulacro por universidad + área académica ────────────
        Route::post('/universidades/{institucionId}/areas/{areaId}/tipos/{tipoId}/iniciar', [AreaAcademicaController::class, 'iniciarUniversidad'])->name('universidad-tipo-iniciar');
    });

    // ─── Examen diagnóstico general ─────────────────────────
    Route::prefix('diagnostico')->name('diagnostico.')->group(function () {
        Route::get('/', [DiagnosticoController::class, 'index'])->name('index');
        Route::post('/iniciar', [DiagnosticoController::class, 'iniciar'])->name('iniciar');
        Route::get('/rendir/{intento}', [DiagnosticoController::class, 'rendir'])->name('rendir');
        Route::post('/rendir/{intento}/guardar', [DiagnosticoController::class, 'guardarRespuesta'])->name('guardar');
        Route::post('/rendir/{intento}/guardar-masivo', [DiagnosticoController::class, 'guardarMasivo'])->name('guardar-masivo');
        Route::post('/rendir/{intento}/finalizar', [DiagnosticoController::class, 'finalizar'])->name('finalizar');
        Route::get('/rendir/{intento}/resultados', [DiagnosticoController::class, 'resultados'])->name('resultados');
    });
});

// ─── Admin: requisitos por carrera ──────────────────────────────
Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/requisitos-carrera', [AdminRequisitosCarreraController::class, 'index'])->name('requisitos-carrera');
    Route::post('/requisitos-carrera', [AdminRequisitosCarreraController::class, 'store'])->name('requisitos-carrera.store');
    Route::post('/requisitos-carrera/puntaje-minimo', [AdminRequisitosCarreraController::class, 'guardarPuntajeMinimo'])->name('requisitos-carrera.puntaje-minimo');
    Route::delete('/requisitos-carrera/{id}', [AdminRequisitosCarreraController::class, 'destroy'])->name('requisitos-carrera.destroy');
    Route::delete('/requisitos-carrera/categoria/{categoriaId}', [AdminRequisitosCarreraController::class, 'destroyAll'])->name('requisitos-carrera.destroy-all');

    // Diagnóstico config
    Route::get('/diagnostico/configurar', [AdminDiagnosticoController::class, 'index'])->name('diagnostico.configurar');
    Route::put('/diagnostico/configurar', [AdminDiagnosticoController::class, 'update'])->name('diagnostico.configurar.update');
});
