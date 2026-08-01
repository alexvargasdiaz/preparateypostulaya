<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\RolUsuario;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Relación: administrador que aprobó al usuario.
     */
    public function aprobadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'aprobado_por');
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'rol',
        'google_id',
        'whatsapp_numero',
        'foto',
        'estado',
        'fecha_aprobacion',
        'aprobado_por',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'rol' => RolUsuario::class,
            'fecha_aprobacion' => 'datetime',
        ];
    }

    /**
     * Verifica si el usuario tiene permisos de administración.
     */
    public function esAdmin(): bool
    {
        return $this->rol?->esAdmin() ?? false;
    }

    /**
     * Verifica si el usuario guarda historial de exámenes.
     */
    public function guardaHistorial(): bool
    {
        return $this->rol?->guardaHistorial() ?? false;
    }

    /**
     * El usuario está activo y aprobado.
     */
    public function estaActivo(): bool
    {
        return $this->estado === 'activo';
    }

    /**
     * El usuario está pendiente de aprobación.
     */
    public function estaPendiente(): bool
    {
        return $this->estado === 'pendiente';
    }

    /**
     * El usuario fue rechazado.
     */
    public function estaRechazado(): bool
    {
        return $this->estado === 'rechazado';
    }

    /**
     * Aprueba al usuario.
     */
    public function aprobar(int $adminId): void
    {
        $this->update([
            'estado' => 'activo',
            'fecha_aprobacion' => now(),
            'aprobado_por' => $adminId,
        ]);
    }

    /**
     * Rechaza al usuario.
     */
    public function rechazar(): void
    {
        $this->update([
            'estado' => 'rechazado',
            'fecha_aprobacion' => null,
            'aprobado_por' => null,
        ]);
    }
}
