<?php

namespace App\Console\Commands;

use App\Console\Commands\Traits\ConfirmsDestructiveOperation;
use Illuminate\Database\Console\WipeCommand;

class ProtectedDbWipe extends WipeCommand
{
    use ConfirmsDestructiveOperation;

    protected $description = 'Elimina todas las tablas, vistas y tipos (requiere confirmación)';
}
