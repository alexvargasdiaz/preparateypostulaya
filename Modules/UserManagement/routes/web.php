<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\UserManagement\Http\Controllers\Admin\AdminAlumnoController;
use Modules\UserManagement\Http\Controllers\Admin\UserController;
use Modules\UserManagement\Http\Controllers\Estudiante\PerfilController;

// ─── Ruta de usuario pendiente (protegida) ───────────────────────
Route::middleware(['auth'])->group(function () {
    Route::get('/pendiente', function () {
        $user = auth()->user();

        return \Inertia\Inertia::render('Auth/Pendiente', [
            'userEmail' => $user?->email,
            'userName' => $user?->name,
            'whatsappNumero' => $user?->whatsapp_numero,
        ]);
    })->name('pendiente');

    Route::post('/pendiente/whatsapp', function (\Illuminate\Http\Request $request) {
        $user = auth()->user();

        if (!$user) {
            return back()->with('error', 'Debes iniciar sesión.');
        }

        $request->validate([
            'whatsapp_numero' => ['required', 'string', 'max:20', new \App\Rules\WhatsappPeruRule],
        ]);

        $user->update([
            'whatsapp_numero' => \App\Support\Telefono::normalizarWhatsApp($request->whatsapp_numero),
        ]);

        return back();
    })->name('pendiente.whatsapp');
});

// ─── Admin: gestión de usuarios ────────────────────────────────
Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/usuarios', [UserController::class, 'index'])->name('usuarios');
    Route::get('/usuarios/crear', [UserController::class, 'crear'])->name('usuarios.crear');
    Route::post('/usuarios', [UserController::class, 'store'])->name('usuarios.store');
    Route::get('/usuarios/{id}/editar', [UserController::class, 'editar'])->name('usuarios.editar');
    Route::put('/usuarios/{id}', [UserController::class, 'actualizar'])->name('usuarios.actualizar');
    Route::delete('/usuarios/{id}', [UserController::class, 'eliminar'])->name('usuarios.eliminar');
    Route::post('/usuarios/{id}/aprobar', [UserController::class, 'aprobar'])->name('usuarios.aprobar');
    Route::post('/usuarios/{id}/rechazar', [UserController::class, 'rechazar'])->name('usuarios.rechazar');

    // Importar / Exportar
    Route::get('/usuarios/exportar', [UserController::class, 'exportar'])->name('usuarios.exportar');
    Route::post('/usuarios/importar', [UserController::class, 'importar'])->name('usuarios.importar');

    // ─── Admin: gestión de alumnos ───────────────────────────────────
    Route::get('/alumnos', [AdminAlumnoController::class, 'index'])->name('alumnos');
    Route::get('/alumnos/crear', [AdminAlumnoController::class, 'crear'])->name('alumnos.crear');
    Route::post('/alumnos', [AdminAlumnoController::class, 'store'])->name('alumnos.store');
    Route::get('/alumnos/{id}/editar', [AdminAlumnoController::class, 'editar'])->name('alumnos.editar');
    Route::put('/alumnos/{id}', [AdminAlumnoController::class, 'actualizar'])->name('alumnos.actualizar');
    Route::delete('/alumnos/{id}', [AdminAlumnoController::class, 'eliminar'])->name('alumnos.eliminar');
    Route::post('/alumnos/{id}/aprobar', [AdminAlumnoController::class, 'aprobar'])->name('alumnos.aprobar');
    Route::post('/alumnos/{id}/rechazar', [AdminAlumnoController::class, 'rechazar'])->name('alumnos.rechazar');

    // Importar / Exportar Alumnos
    Route::get('/alumnos/exportar', [AdminAlumnoController::class, 'exportar'])->name('alumnos.exportar');
    Route::post('/alumnos/importar', [AdminAlumnoController::class, 'importar'])->name('alumnos.importar');
});

// ─── Perfil del estudiante ────────────────────────────────────
Route::middleware(['auth'])->prefix('mi-perfil')->name('perfil.')->group(function () {
    Route::get('/', [PerfilController::class, 'show'])->name('show');
    Route::put('/', [PerfilController::class, 'update'])->name('update');
    Route::post('/foto', [PerfilController::class, 'updateFoto'])->name('update-foto');
    Route::delete('/foto', [PerfilController::class, 'destroyFoto'])->name('destroy-foto');
});
