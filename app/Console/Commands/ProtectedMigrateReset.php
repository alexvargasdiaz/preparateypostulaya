<?php

namespace App\Console\Commands;

use App\Console\Commands\Traits\ConfirmsDestructiveOperation;
use Illuminate\Database\Console\Migrations\ResetCommand;

class ProtectedMigrateReset extends ResetCommand
{
    use ConfirmsDestructiveOperation;

    protected $description = 'Revierte todas las migraciones (requiere confirmación)';
}
