<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Catalogo\Http\Controllers\AdminInstitucionController;
use Modules\Catalogo\Http\Controllers\AdminCategoriaController;

Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    // Universidades
    Route::get('/instituciones', [AdminInstitucionController::class, 'index'])->name('instituciones.index');
    Route::get('/instituciones/crear', [AdminInstitucionController::class, 'create'])->name('instituciones.create');
    Route::post('/instituciones', [AdminInstitucionController::class, 'store'])->name('instituciones.store');
    Route::get('/instituciones/{id}/editar', [AdminInstitucionController::class, 'edit'])->name('instituciones.edit');
    Route::put('/instituciones/{id}', [AdminInstitucionController::class, 'update'])->name('instituciones.update');
    Route::delete('/instituciones/{id}', [AdminInstitucionController::class, 'destroy'])->name('instituciones.destroy');

    // Carreras (Categorías)
    Route::get('/categorias', [AdminCategoriaController::class, 'index'])->name('categorias.index');
    Route::get('/categorias/crear', [AdminCategoriaController::class, 'create'])->name('categorias.create');
    Route::post('/categorias', [AdminCategoriaController::class, 'store'])->name('categorias.store');
    Route::get('/categorias/{id}/editar', [AdminCategoriaController::class, 'edit'])->name('categorias.edit');
    Route::put('/categorias/{id}', [AdminCategoriaController::class, 'update'])->name('categorias.update');
    Route::delete('/categorias/{id}', [AdminCategoriaController::class, 'destroy'])->name('categorias.destroy');
});
