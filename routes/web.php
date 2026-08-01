<?php

declare(strict_types=1);

use App\Http\Controllers\HomeController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// ─── Debug Session ──────────────────────────────────────────
Route::get('/debug-session', function () {
    $session = request()->session();
    $cookieName = config('session.cookie');
    $cookieValue = request()->cookie($cookieName);

    $sessionId = $session->getId();
    $user = auth()->user();

    $loginKeys = [];
    foreach ($session->all() as $key => $value) {
        if (str_contains((string) $key, 'login_web')) {
            $loginKeys[] = $key;
        }
    }

    return response()->json([
        'session_id' => $sessionId,
        'cookie_value' => $cookieValue ? substr($cookieValue, 0, 20).'...' : null,
        'cookie_name' => $cookieName,
        'has_session_file' => file_exists(storage_path('framework/sessions/'.$sessionId)),
        'login_keys_in_session' => $loginKeys,
        'is_authenticated' => $user ? true : false,
        'user_id' => $user?->id,
        'user_rol' => $user?->rol,
        'session_file_count' => count(glob(storage_path('framework/sessions/*'))),
    ]);
});

// ─── Página principal ──────────────────────────────────────────
Route::get('/', [HomeController::class, 'index']);
