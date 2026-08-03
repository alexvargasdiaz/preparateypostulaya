<?php

namespace App\Console\Commands;

use App\Console\Commands\Traits\ConfirmsDestructiveOperation;
use Illuminate\Database\Console\Migrations\RefreshCommand;

class ProtectedMigrateRefresh extends RefreshCommand
{
    use ConfirmsDestructiveOperation;

    protected $description = 'Reinicia y re-ejecuta todas las migraciones (requiere confirmación)';
}
