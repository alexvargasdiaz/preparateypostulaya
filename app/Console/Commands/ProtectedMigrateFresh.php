<?php

namespace App\Console\Commands;

use App\Console\Commands\Traits\ConfirmsDestructiveOperation;
use Illuminate\Database\Console\Migrations\FreshCommand;

class ProtectedMigrateFresh extends FreshCommand
{
    use ConfirmsDestructiveOperation;

    protected $description = 'Borra todas las tablas y re-ejecuta las migraciones (requiere confirmación)';
}
