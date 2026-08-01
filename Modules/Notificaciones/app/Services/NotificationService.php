<?php

declare(strict_types=1);

namespace Modules\Notificaciones\Services;

use App\Models\User;
use Modules\Notificaciones\Models\Notificacion;
use Modules\Notificaciones\Models\PreferenciaNotificacion;

class NotificationService
{
    /**
     * Crea una notificación in-app para un usuario.
     *
     * @param int|User $usuario
     * @param string $tipo    info|exito|advertencia|error
     * @param string $titulo
     * @param string|null $mensaje
     * @param array|null $data    Datos adicionales (url, id_relacionado, etc.)
     * @param string|null $icono  Emoji opcional
     * @param string $audiencia   admin|alumno|all
     * @return Notificacion
     */
    public function crear(
        int|User $usuario,
        string $tipo = 'info',
        string $titulo,
        ?string $mensaje = null,
        ?array $data = null,
        ?string $icono = null,
        string $audiencia = 'alumno',
    ): Notificacion {
        $usuarioId = is_object($usuario) ? $usuario->id : $usuario;

        return Notificacion::create([
            'usuario_id' => $usuarioId,
            'audiencia' => $audiencia,
            'tipo' => $tipo,
            'titulo' => $titulo,
            'mensaje' => $mensaje,
            'data' => $data,
            'icono' => $icono ?? $this->iconoPorTipo($tipo),
            'leida' => false,
        ]);
    }

    /**
     * Notifica que un examen fue completado (alumno).
     */
    public function examenCompletado(
        int|User $usuario,
        string $examenTitulo,
        int $puntaje,
        int $maximo,
        bool $aprobado,
        int $intentoId,
    ): Notificacion {
        $porcentaje = $maximo > 0 ? round(($puntaje / $maximo) * 100) : 0;

        return $this->crear(
            usuario: $usuario,
            tipo: $aprobado ? 'exito' : 'info',
            titulo: $aprobado
                ? "¡Aprobaste {$examenTitulo}!"
                : "Completaste {$examenTitulo}",
            mensaje: $aprobado
                ? "Obtuviste {$puntaje}/{$maximo} ({$porcentaje}%). ¡Sigue así!"
                : "Obtuviste {$puntaje}/{$maximo} ({$porcentaje}%). Revisa las áreas que necesitas reforzar.",
            data: [
                'url' => "/resultados/{$intentoId}",
                'intento_id' => $intentoId,
                'puntaje' => $puntaje,
                'maximo' => $maximo,
                'porcentaje' => $porcentaje,
                'aprobado' => $aprobado,
            ],
            icono: $aprobado ? '🎉' : '📝',
            audiencia: 'alumno',
        );
    }

    /**
     * Notifica un hito o logro alcanzado (alumno).
     */
    public function logroAlcanzado(
        int|User $usuario,
        string $titulo,
        string $mensaje,
        ?string $url = null,
    ): Notificacion {
        return $this->crear(
            usuario: $usuario,
            tipo: 'exito',
            titulo: "🏆 {$titulo}",
            mensaje: $mensaje,
            data: $url ? ['url' => $url] : null,
            icono: '🏆',
            audiencia: 'alumno',
        );
    }

    /**
     * Notifica un recordatorio de estudio (alumno).
     */
    public function recordatorioEstudio(
        int|User $usuario,
        int $diasSinPracticar,
        ?string $ultimoExamen = null,
    ): Notificacion {
        $mensajes = $this->generarMensajeRecordatorio($diasSinPracticar, $ultimoExamen);

        return $this->crear(
            usuario: $usuario,
            tipo: $diasSinPracticar >= 14 ? 'advertencia' : 'info',
            titulo: $mensajes['titulo'],
            mensaje: $mensajes['mensaje'],
            data: [
                'url' => '/examenes',
                'dias_sin_practicar' => $diasSinPracticar,
                'ultimo_examen' => $ultimoExamen,
            ],
            icono: $diasSinPracticar >= 14 ? '⏰' : '📚',
            audiencia: 'alumno',
        );
    }

    // ═══════════════════════════════════════════════════════════
    //  NOTIFICACIONES PARA ADMIN
    // ═══════════════════════════════════════════════════════════

    /**
     * Notifica a admins que un nuevo alumno se registró (admin).
     */
    public function alumnoRegistrado(
        int|User $admin,
        string $nombreAlumno,
        string $emailAlumno,
        int $alumnoId,
    ): Notificacion {
        return $this->crear(
            usuario: $admin,
            tipo: 'info',
            titulo: 'Nuevo alumno registrado',
            mensaje: "{$nombreAlumno} ({$emailAlumno}) se ha registrado y está pendiente de aprobación.",
            data: [
                'url' => "/admin/alumnos/{$alumnoId}",
                'alumno_id' => $alumnoId,
                'nombre' => $nombreAlumno,
            ],
            icono: '👤',
            audiencia: 'admin',
        );
    }

    /**
     * Notifica a admins que un alumno fue aprobado (admin).
     */
    public function alumnoAprobado(
        int|User $admin,
        string $nombreAlumno,
        int $alumnoId,
    ): Notificacion {
        return $this->crear(
            usuario: $admin,
            tipo: 'exito',
            titulo: 'Alumno aprobado',
            mensaje: "{$nombreAlumno} ha sido aprobado y ya puede acceder a la plataforma.",
            data: [
                'url' => "/admin/alumnos/{$alumnoId}",
                'alumno_id' => $alumnoId,
            ],
            icono: '✅',
            audiencia: 'admin',
        );
    }

    /**
     * Notifica a admins que un alumno fue rechazado (admin).
     */
    public function alumnoRechazado(
        int|User $admin,
        string $nombreAlumno,
        int $alumnoId,
    ): Notificacion {
        return $this->crear(
            usuario: $admin,
            tipo: 'advertencia',
            titulo: 'Alumno rechazado',
            mensaje: "{$nombreAlumno} ha sido rechazado y no tendrá acceso a la plataforma.",
            data: [
                'url' => "/admin/alumnos/{$alumnoId}",
                'alumno_id' => $alumnoId,
            ],
            icono: '🚫',
            audiencia: 'admin',
        );
    }

    /**
     * Notifica a admins que un nuevo examen fue creado (admin).
     */
    public function examenCreado(
        int|User $admin,
        string $tituloExamen,
        string $carrera,
        int $examenId,
    ): Notificacion {
        return $this->crear(
            usuario: $admin,
            tipo: 'info',
            titulo: 'Nuevo examen creado',
            mensaje: "Se creó el examen \"{$tituloExamen}\" para la carrera {$carrera}.",
            data: [
                'url' => "/admin/examenes/{$examenId}/editar",
                'examen_id' => $examenId,
            ],
            icono: '📋',
            audiencia: 'admin',
        );
    }

    /**
     * Notifica a admins sobre alertas del sistema (admin).
     */
    public function alertaSistema(
        int|User $admin,
        string $titulo,
        string $mensaje,
        ?string $url = null,
    ): Notificacion {
        return $this->crear(
            usuario: $admin,
            tipo: 'advertencia',
            titulo: "⚠️ {$titulo}",
            mensaje: $mensaje,
            data: $url ? ['url' => $url] : null,
            icono: '⚠️',
            audiencia: 'admin',
        );
    }

    // ═══════════════════════════════════════════════════════════
    //  MÉTODOS COMPARTIDOS
    // ═══════════════════════════════════════════════════════════

    /**
     * Genera el mensaje del recordatorio según los días sin practicar.
     *
     * @return array{titulo: string, mensaje: string}
     */
    private function generarMensajeRecordatorio(int $dias, ?string $ultimoExamen): array
    {
        if ($dias <= 3) {
            return [
                'titulo' => '📚 ¡Sigue practicando!',
                'mensaje' => 'Hace unos días que no practicas. Un simulacro rápido te mantendrá en ritmo.',
            ];
        }

        if ($dias <= 7) {
            return [
                'titulo' => '📚 ¡No pierdas el hábito!',
                'mensaje' => $ultimoExamen
                    ? "Han pasado {$dias} días desde tu último simulacro ({$ultimoExamen}). Retoma el ritmo con un nuevo intento."
                    : "Han pasado {$dias} días desde tu último simulacro. ¡Vuelve a practicar!",
            ];
        }

        if ($dias <= 14) {
            return [
                'titulo' => '⏰ ¡Te estamos esperando!',
                'mensaje' => $ultimoExamen
                    ? "Ya pasaron {$dias} días desde \"{$ultimoExamen}\". La práctica constante es clave para ingresar. ¡Anímate con un nuevo simulacro!"
                    : "Ya pasaron {$dias} días desde tu último simulacro. La práctica constante es clave para ingresar. ¡Anímate!",
            ];
        }

        return [
            'titulo' => '⏰ ¡No dejes pasar más tiempo!',
            'mensaje' => $ultimoExamen
                ? "Han pasado {$dias} días desde \"{$ultimoExamen}\". Cada día cuenta en tu preparación. ¡Rinde un simulacro ahora!"
                : "Han pasado {$dias} días desde tu último simulacro. Cada día cuenta en tu preparación. ¡Empieza ahora!",
        ];
    }

    /**
     * Obtiene el conteo de notificaciones no leídas de un usuario.
     */
    public function contarNoLeidas(int $usuarioId, ?string $audiencia = null): int
    {
        $query = Notificacion::where('usuario_id', $usuarioId)
            ->where('leida', false);

        if ($audiencia) {
            $query->where(function ($q) use ($audiencia) {
                $q->where('audiencia', $audiencia)
                  ->orWhere('audiencia', 'all');
            });
        }

        return $query->count();
    }

    /**
     * Obtiene las notificaciones recientes de un usuario.
     */
    public function obtenerRecientes(int $usuarioId, int $limite = 10, ?string $audiencia = null): array
    {
        $query = Notificacion::where('usuario_id', $usuarioId);

        if ($audiencia) {
            $query->where(function ($q) use ($audiencia) {
                $q->where('audiencia', $audiencia)
                  ->orWhere('audiencia', 'all');
            });
        }

        return $query->orderBy('created_at', 'desc')
            ->limit($limite)
            ->get()
            ->toArray();
    }

    /**
     * Marca una notificación como leída.
     */
    public function marcarLeida(int $notificacionId, int $usuarioId): bool
    {
        $notificacion = Notificacion::where('id', $notificacionId)
            ->where('usuario_id', $usuarioId)
            ->first();

        if (!$notificacion) {
            return false;
        }

        $notificacion->marcarComoLeida();
        return true;
    }

    /**
     * Marca todas las notificaciones como leídas.
     */
    public function marcarTodasLeidas(int $usuarioId, ?string $audiencia = null): int
    {
        $query = Notificacion::where('usuario_id', $usuarioId)
            ->where('leida', false);

        if ($audiencia) {
            $query->where(function ($q) use ($audiencia) {
                $q->where('audiencia', $audiencia)
                  ->orWhere('audiencia', 'all');
            });
        }

        return $query->update([
            'leida' => true,
            'leida_at' => now(),
        ]);
    }

    /**
     * Icono por defecto según el tipo.
     */
    private function iconoPorTipo(string $tipo): string
    {
        return match ($tipo) {
            'exito' => '✅',
            'advertencia' => '⚠️',
            'error' => '❌',
            default => 'ℹ️',
        };
    }
}
