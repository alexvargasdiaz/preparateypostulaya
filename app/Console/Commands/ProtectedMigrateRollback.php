<?php

namespace App\Console\Commands;

use App\Console\Commands\Traits\ConfirmsDestructiveOperation;
use Illuminate\Database\Console\Migrations\RollbackCommand;

class ProtectedMigrateRollback extends RollbackCommand
{
    use ConfirmsDestructiveOperation;

    protected $description = 'Revierte la última migración (requiere confirmación)';
}
