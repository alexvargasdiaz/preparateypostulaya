<?php

declare(strict_types=1);

namespace Modules\Rendicion\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Preguntas\Models\Alternativa;
use Modules\Preguntas\Models\Pregunta;

class RespuestaUsuario extends Model
{
    protected $table = 'respuestas_usuario';

    protected $fillable = [
        'intento_id',
        'pregunta_id',
        'alternativa_id_elegida',
        'es_correcta',
        'tiempo_respuesta_seg',
    ];

    protected function casts(): array
    {
        return [
            'es_correcta' => 'boolean',
            'tiempo_respuesta_seg' => 'decimal:2',
        ];
    }

    public function intento(): BelongsTo
    {
        return $this->belongsTo(IntentoExamen::class, 'intento_id');
    }

    public function pregunta(): BelongsTo
    {
        return $this->belongsTo(Pregunta::class);
    }

    public function alternativaElegida(): BelongsTo
    {
        return $this->belongsTo(Alternativa::class, 'alternativa_id_elegida');
    }
}
