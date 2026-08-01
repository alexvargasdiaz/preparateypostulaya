<?php

declare(strict_types=1);

namespace Modules\Notificaciones\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $usuario_id
 * @property string $audiencia  (admin, alumno, all)
 * @property string $tipo       (info, exito, advertencia, error)
 * @property string $titulo
 * @property string|null $mensaje
 * @property array|null $data   (url, id_relacionado, etc.)
 * @property string|null $icono (emoji)
 * @property bool $leida
 * @property string|null $leida_at
 * @property string $created_at
 * @property string $updated_at
 */
class Notificacion extends Model
{
    protected $table = 'notifications';

    protected $fillable = [
        'usuario_id',
        'audiencia',
        'tipo',
        'titulo',
        'mensaje',
        'data',
        'icono',
        'leida',
        'leida_at',
    ];

    protected function casts(): array
    {
        return [
            'data' => 'array',
            'leida' => 'boolean',
            'leida_at' => 'datetime',
        ];
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    /**
     * Marca la notificación como leída.
     */
    public function marcarComoLeida(): void
    {
        if (!$this->leida) {
            $this->update([
                'leida' => true,
                'leida_at' => now(),
            ]);
        }
    }
}
