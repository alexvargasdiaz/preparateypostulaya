<?php

declare(strict_types=1);

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Modules\Auth\Http\Controllers\GoogleController;
use Modules\Auth\Http\Controllers\LoginController;
use Modules\Auth\Http\Controllers\RegisterController;

// ─── Login local (email/contraseña) ────────────────────────────
Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login']);

// ─── Registro local ────────────────────────────────────────────
Route::get('/register', [RegisterController::class, 'showForm'])->name('register');
Route::post('/register', [RegisterController::class, 'register']);

// ─── Autenticación con Google ──────────────────────────────────
Route::prefix('auth')->name('auth.')->group(function () {
    Route::get('/google', [GoogleController::class, 'redirect'])->name('google');
    Route::get('/google/callback', [GoogleController::class, 'callback'])->name('google.callback');
});

// ─── Cerrar sesión ───────────────────────────────────────────
Route::middleware(['auth'])->post('/logout', function (Request $request) {
    $userId = $request->user()?->id;
    auth()->logout();
    $request->session()->invalidate();
    $request->session()->regenerateToken();

    if ($userId) {
        \DB::table('sessions')->where('user_id', $userId)->delete();
    }

    $response = redirect('/');
    $response->headers->clearCookie(
        config('session.cookie'),
        config('session.path'),
        config('session.domain')
    );

    return $response;
})->name('logout');
