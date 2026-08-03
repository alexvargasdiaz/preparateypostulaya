<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Fuerza el entorno de pruebas ANTES de que se cree la aplicación.
     *
     * El proceso `php artisan test` arranca la app con el `.env` real, dejando
     * valores en $_ENV (APP_ENV=local, DB_DATABASE=preparateypostulaya) que
     * impedirían seleccionar `.env.testing` y harían que los tests tocaran la
     * base de datos real. Aquí limpiamos esos restos y aseguramos que los tests
     * usen siempre la BD aislada definida en `.env.testing`.
     *
     * Las credenciales de la BD se toman del entorno del proceso (cargado desde
     * el `.env` real) para no guardar secretos en archivos versionados.
     */
    protected function setUp(): void
    {
        $dbCredentials = [];
        foreach (['DB_HOST', 'DB_PORT', 'DB_USERNAME', 'DB_PASSWORD'] as $variable) {
            $dbCredentials[$variable] = $_ENV[$variable] ?? getenv($variable);
        }

        foreach (['APP_ENV', 'DB_CONNECTION', 'DB_DATABASE', 'DB_HOST', 'DB_PORT', 'DB_USERNAME', 'DB_PASSWORD', 'DB_URL'] as $variable) {
            unset($_ENV[$variable], $_SERVER[$variable]);
            putenv($variable);
        }

        $_ENV['APP_ENV'] = 'testing';
        putenv('APP_ENV=testing');

        foreach ($dbCredentials as $variable => $value) {
            if ($value !== false && $value !== null) {
                $_ENV[$variable] = $value;
                $_SERVER[$variable] = $value;
                putenv("{$variable}={$value}");
            }
        }

        parent::setUp();
    }
}
