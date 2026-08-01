<?php

declare(strict_types=1);

namespace App\Enums;

enum RolUsuario: string
{
    case SuperAdmin = 'super_admin';
    case Admin = 'admin';
    case Estudiante = 'estudiante';
    case Invitado = 'invitado';

    /**
     * Roles que tienen acceso al panel de administración.
     */
    public function esAdmin(): bool
    {
        return in_array($this, [self::SuperAdmin, self::Admin], true);
    }

    /**
     * Roles que pueden guardar historial de exámenes.
     * Solo los estudiantes rinden exámenes y generan historial.
     */
    public function guardaHistorial(): bool
    {
        return $this === self::Estudiante;
    }

    /**
     * Label amigable en español.
     */
    public function label(): string
    {
        return match ($this) {
            self::SuperAdmin => 'Super Administrador',
            self::Admin => 'Administrador',
            self::Estudiante => 'Estudiante',
            self::Invitado => 'Invitado',
        };
    }
}
