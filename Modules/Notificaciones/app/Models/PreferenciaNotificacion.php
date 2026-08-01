<?php

declare(strict_types=1);

namespace Modules\Notificaciones\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $usuario_id
 * @property bool $email_resultados
 * @property bool $whatsapp_resultados
 * @property bool $recordatorio_estudio
 * @property bool $novedades
 */
class PreferenciaNotificacion extends Model
{
    protected $table = 'notification_preferences';

    protected $fillable = [
        'usuario_id',
        'email_resultados',
        'whatsapp_resultados',
        'recordatorio_estudio',
        'novedades',
    ];

    protected function casts(): array
    {
        return [
            'email_resultados' => 'boolean',
            'whatsapp_resultados' => 'boolean',
            'recordatorio_estudio' => 'boolean',
            'novedades' => 'boolean',
        ];
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    /**
     * Obtiene o crea las preferencias por defecto para un usuario.
     */
    public static function paraUsuario(int $usuarioId): self
    {
        return static::firstOrCreate(
            ['usuario_id' => $usuarioId],
            [
                'email_resultados' => true,
                'whatsapp_resultados' => false,
                'recordatorio_estudio' => true,
                'novedades' => true,
            ]
        );
    }
}
