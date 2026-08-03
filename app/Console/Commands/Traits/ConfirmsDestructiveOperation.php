<?php

namespace App\Console\Commands\Traits;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Agrega confirmación obligatoria a comandos de consola destructivos
 * (migrate:fresh, db:wipe, migrate:refresh, migrate:reset y migrate:rollback).
 *
 * Reglas:
 * - Durante la ejecución de tests se omite la confirmación (RefreshDatabase
 *   ejecuta migrate:fresh internamente y no debe quedarse bloqueado).
 * - El flag --force es la confirmación explícita del operador y omite la pregunta.
 */
trait ConfirmsDestructiveOperation
{
    /**
     * Mensaje de advertencia mostrado antes de ejecutar el comando.
     */
    protected function destructiveOperationWarning(): string
    {
        // Usa la configuración (sin conectar) para que la advertencia funcione
        // incluso si la base de datos está caída.
        $database = config('database.connections.'.config('database.default').'.database', '?');

        return "⚠️  ¡CUIDADO! Este comando BORRA datos de la base de datos [{$database}] de forma irreversible. Escribe 'yes' para confirmar.";
    }

    /**
     * Pide confirmación antes de ejecutar el comando destructivo.
     */
    public function handle()
    {
        if ($this->getLaravel()->runningUnitTests()) {
            return parent::handle();
        }

        if (! $this->option('force')) {
            // Sin terminal interactiva (tests, scripts, CI, otras herramientas) se cancela
            // siempre; solo en una terminal real se muestra la pregunta de confirmación.
            $interactive = function_exists('stream_isatty') && @stream_isatty(STDIN);

            $confirmed = $interactive
                ? $this->confirm($this->destructiveOperationWarning(), false)
                : false;

            if (! $confirmed) {
                $this->components->warn('Operación cancelada. No se modificó la base de datos.');

                return Command::FAILURE;
            }
        }

        return parent::handle();
    }

    /**
     * La confirmación ya se gestionó en handle(); evita que el comando padre
     * vuelva a preguntar en entornos de producción.
     */
    public function confirmToProceed($warning = 'Application In Production', $callback = null)
    {
        return true;
    }
}
