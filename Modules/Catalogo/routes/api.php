<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Catalogo\Http\Controllers\TipoExamenController;
use Modules\Catalogo\Http\Controllers\InstitucionController;
use Modules\Catalogo\Http\Controllers\CategoriaController;
use Modules\Catalogo\Http\Controllers\ExamenController;

/*
|--------------------------------------------------------------------------
| API Routes — Módulo Catálogo
|--------------------------------------------------------------------------
|
| Endpoints para la gestión del catálogo de exámenes genérico.
| Versión: v1
|
*/

Route::prefix('v1/catalogo')->group(function () {

    // ─── Tipo de Examen ─────────────────────────────────────────
    Route::apiResource('tipos-examen', TipoExamenController::class)
        ->parameters(['tipos-examen' => 'tipoExamen']);

    // ─── Instituciones ───────────────────────────────────────────
    Route::apiResource('instituciones', InstitucionController::class);

    // ─── Categorías ──────────────────────────────────────────────
    Route::apiResource('categorias', CategoriaController::class);

    // ─── Exámenes ────────────────────────────────────────────────
    Route::apiResource('examenes', ExamenController::class);
});
