<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Preguntas\Http\Controllers\AdminExamenController;
use Modules\Preguntas\Http\Controllers\AdminPreguntasController;
use Modules\Preguntas\Http\Controllers\AdminBaulPreguntasController;
use Modules\Preguntas\Http\Controllers\AdminAreaAcademicaController;
use Modules\Preguntas\Http\Controllers\AdminTipoSimulacroController;

Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    // CRUD de exámenes
    Route::get('/examenes', [AdminExamenController::class, 'index'])->name('examenes.index');
    Route::get('/examenes/crear', [AdminExamenController::class, 'create'])->name('examenes.create');
    Route::post('/examenes', [AdminExamenController::class, 'store'])->name('examenes.store');
    Route::get('/examenes/{id}/editar', [AdminExamenController::class, 'edit'])->name('examenes.edit');
    Route::put('/examenes/{id}', [AdminExamenController::class, 'update'])->name('examenes.update');
    Route::delete('/examenes/{id}', [AdminExamenController::class, 'destroy'])->name('examenes.destroy');

    // CRUD de preguntas
    Route::resource('preguntas', AdminPreguntasController::class)
        ->except(['show']);

    // Upload de imágenes
    Route::post('/preguntas/subir-imagen', [AdminPreguntasController::class, 'subirImagen'])
        ->name('preguntas.subir-imagen');

    // Importación masiva
    Route::get('/preguntas/importar', [AdminPreguntasController::class, 'importarForm'])->name('preguntas.importar.form');
    Route::post('/preguntas/importar', [AdminPreguntasController::class, 'importar'])->name('preguntas.importar');
    Route::post('/preguntas/subir-imagenes-masivo', [AdminPreguntasController::class, 'subirImagenesMasivo'])->name('preguntas.subir-imagenes-masivo');

    // Baúl de Preguntas
    Route::get('/baul-preguntas', [AdminBaulPreguntasController::class, 'index'])->name('baul-preguntas.index');
    Route::post('/baul-preguntas/actualizar-area', [AdminBaulPreguntasController::class, 'actualizarArea'])->name('baul-preguntas.actualizar-area');
    Route::post('/baul-preguntas/actualizar-masivo', [AdminBaulPreguntasController::class, 'actualizarMasivo'])->name('baul-preguntas.actualizar-masivo');

    // Áreas Académicas
    Route::get('/areas-academicas', [AdminAreaAcademicaController::class, 'index'])->name('areas-academicas.index');
    Route::post('/areas-academicas', [AdminAreaAcademicaController::class, 'store'])->name('areas-academicas.store');
    Route::put('/areas-academicas/{id}', [AdminAreaAcademicaController::class, 'update'])->name('areas-academicas.update');
    Route::delete('/areas-academicas/{id}', [AdminAreaAcademicaController::class, 'destroy'])->name('areas-academicas.destroy');

    // Tipos de Simulacro (por área)
    Route::get('/tipos-simulacro', [AdminTipoSimulacroController::class, 'index'])->name('tipos-simulacro.index');
    Route::post('/tipos-simulacro', [AdminTipoSimulacroController::class, 'store'])->name('tipos-simulacro.store');
    Route::put('/tipos-simulacro/{id}', [AdminTipoSimulacroController::class, 'update'])->name('tipos-simulacro.update');
    Route::delete('/tipos-simulacro/{id}', [AdminTipoSimulacroController::class, 'destroy'])->name('tipos-simulacro.destroy');
});
