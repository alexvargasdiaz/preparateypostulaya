<?php

namespace Tests\Feature;

use App\Console\Commands\ProtectedDbWipe;
use App\Console\Commands\ProtectedMigrateFresh;
use App\Console\Commands\ProtectedMigrateRefresh;
use App\Console\Commands\ProtectedMigrateReset;
use App\Console\Commands\ProtectedMigrateRollback;
use Illuminate\Database\Console\Migrations\FreshCommand;
use Illuminate\Database\Console\Migrations\RefreshCommand;
use Illuminate\Database\Console\Migrations\ResetCommand;
use Illuminate\Database\Console\Migrations\RollbackCommand;
use Illuminate\Database\Console\WipeCommand;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DestructiveCommandsProtectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_los_comandos_destructivos_usan_las_clases_protegidas(): void
    {
        $this->assertInstanceOf(ProtectedMigrateFresh::class, app(FreshCommand::class));
        $this->assertInstanceOf(ProtectedDbWipe::class, app(WipeCommand::class));
        $this->assertInstanceOf(ProtectedMigrateRefresh::class, app(RefreshCommand::class));
        $this->assertInstanceOf(ProtectedMigrateReset::class, app(ResetCommand::class));
        $this->assertInstanceOf(ProtectedMigrateRollback::class, app(RollbackCommand::class));
    }

    public function test_migrate_fresh_sin_confirmacion_no_elimina_tablas(): void
    {
        $this->assertTrue(Schema::hasTable('users'));

        $status = $this->ejecutarSinConfirmacion('migrate:fresh');

        $this->assertSame(1, $status);
        $this->assertTrue(Schema::hasTable('users'), 'migrate:fresh no debe eliminar tablas sin confirmación.');
    }

    public function test_db_wipe_sin_confirmacion_no_elimina_tablas(): void
    {
        $this->assertTrue(Schema::hasTable('users'));

        $status = $this->ejecutarSinConfirmacion('db:wipe');

        $this->assertSame(1, $status);
        $this->assertTrue(Schema::hasTable('users'), 'db:wipe no debe eliminar tablas sin confirmación.');
    }

    public function test_migrate_refresh_sin_confirmacion_no_ejecuta(): void
    {
        $this->assertSame(1, $this->ejecutarSinConfirmacion('migrate:refresh'));
        $this->assertTrue(Schema::hasTable('users'));
    }

    public function test_migrate_reset_sin_confirmacion_no_ejecuta(): void
    {
        $this->assertSame(1, $this->ejecutarSinConfirmacion('migrate:reset'));
        $this->assertTrue(Schema::hasTable('users'));
    }

    public function test_migrate_rollback_sin_confirmacion_no_ejecuta(): void
    {
        $this->assertSame(1, $this->ejecutarSinConfirmacion('migrate:rollback'));
        $this->assertTrue(Schema::hasTable('users'));
    }

    public function test_migrate_fresh_con_force_ejecuta_correctamente(): void
    {
        $status = $this->ejecutarSinConfirmacion('migrate:fresh', ['--force' => true]);

        $this->assertSame(0, $status);
        $this->assertTrue(Schema::hasTable('users'));
    }

    /**
     * Ejecuta un comando simulando un entorno que NO es de testing
     * (para que la confirmación entre en juego) y sin interacción.
     */
    private function ejecutarSinConfirmacion(string $command, array $parameters = []): int
    {
        $this->app['env'] = 'local';

        try {
            return $this->artisan($command, [...$parameters, '--no-interaction' => true])->run();
        } finally {
            $this->app['env'] = 'testing';
        }
    }
}
