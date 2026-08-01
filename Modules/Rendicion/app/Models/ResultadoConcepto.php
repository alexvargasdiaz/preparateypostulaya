<?php

declare(strict_types=1);

namespace Modules\Rendicion\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Preguntas\Models\Concepto;

class ResultadoConcepto extends Model
{
    protected $table = 'resultados_conceptos';

    protected $fillable = [
        'intento_id',
        'concepto_id',
        'preguntas_totales',
        'preguntas_correctas',
        'porcentaje_acierto',
    ];

    protected function casts(): array
    {
        return [
            'preguntas_totales' => 'integer',
            'preguntas_correctas' => 'integer',
            'porcentaje_acierto' => 'decimal:2',
        ];
    }

    public function intento(): BelongsTo
    {
        return $this->belongsTo(IntentoExamen::class, 'intento_id');
    }

    public function concepto(): BelongsTo
    {
        return $this->belongsTo(Concepto::class);
    }
}
