<?php

declare(strict_types=1);

namespace Modules\Preguntas\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Preguntas\Models\Concepto;

class MensajeAyuda extends Model
{
    protected $table = 'mensajes_ayuda';

    protected $fillable = [
        'concepto_id',
        'texto',
        'umbral_porcentaje_acierto',
        'activo',
    ];

    protected function casts(): array
    {
        return [
            'umbral_porcentaje_acierto' => 'integer',
            'activo' => 'boolean',
        ];
    }

    public function concepto(): BelongsTo
    {
        return $this->belongsTo(Concepto::class);
    }
}
