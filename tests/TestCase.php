<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Fuerza el entorno de pruebas ANTES de que se cree la aplicación.
     *
     * El proceso `php artisan test` arranca la app con el `.env` real y luego
     * borra esas variables del entorno antes de lanzar PHPUnit (Collision
     * TestCommand::clearEnv). El proceso hijo de PHPUnit, a su vez, solo carga
     * `.env.testing` al crear la aplicación, así que las credenciales de la BD
     * (contraseña, usuario, host) nunca llegaban a la conexión de pruebas.
     *
     * Aquí volvemos a cargar el `.env` real (no versionado) para recuperar esas
     * credenciales, y aseguramos que los tests usen siempre la BD aislada
     * definida en `.env.testing` sin tocar la base de datos real.
     */
    protected function setUp(): void
    {
        $this->cargarCredencialesReales();

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

    /**
     * Recupera las credenciales reales de la BD desde el `.env` del proyecto.
     *
     * Se usa el mismo repositorio inmutable que emplea el propio arranque de
     * Laravel (Env::getRepository), por lo que las variables ya definidas por
     * phpunit.xml o `.env.testing` (APP_ENV, DB_DATABASE, DB_URL...) no se
     * sobrescriben: solo se añaden las que faltan, como DB_PASSWORD.
     */
    private function cargarCredencialesReales(): void
    {
        $ruta = dirname(__DIR__);

        if (is_file($ruta.'/.env')) {
            \Dotenv\Dotenv::create(
                \Illuminate\Support\Env::getRepository(),
                $ruta,
                '.env'
            )->safeLoad();
        }
    }
}
