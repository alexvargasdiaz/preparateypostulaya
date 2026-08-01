<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Rutas de la API versionada del sistema.
| Cada módulo expone sus rutas bajo /api/v1/{modulo}/...
|
*/

Route::prefix('api')->group(function () {
    // Las rutas de cada módulo se cargan desde Modules/{Modulo}/routes/api.php
    // automáticamente a través del ServiceProvider de nwidart/laravel-modules.
});
