<?php

declare(strict_types=1);

namespace Modules\Preguntas\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DiagnosticoConcepto extends Model
{
    protected $table = 'diagnostico_concepto';

    protected $fillable = [
        'concepto_id',
        'preguntas_por_concepto',
        'duracion_minutos',
    ];

    public function concepto(): BelongsTo
    {
        return $this->belongsTo(Concepto::class);
    }
}
