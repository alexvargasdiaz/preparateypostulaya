<?php

declare(strict_types=1);

namespace Modules\Preguntas\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Modules\Preguntas\Models\Concepto;
use Modules\Catalogo\Models\Seccion;

class Pregunta extends Model
{
    protected $table = 'preguntas';

    protected $fillable = [
        'examen_id',
        'area_academica_id',
        'nivel',
        'seccion_id',
        'concepto_id',
        'enunciado',
        'enunciado_imagen_url',
        'tipo',
        'dificultad',
        'orden',
        'activa',
    ];

    protected function casts(): array
    {
        return [
            'orden' => 'integer',
            'nivel' => 'integer',
            'activa' => 'boolean',
        ];
    }

    public function areaAcademica(): BelongsTo
    {
        return $this->belongsTo(AreaAcademica::class);
    }

    public function seccion(): BelongsTo
    {
        return $this->belongsTo(Seccion::class);
    }

    public function concepto(): BelongsTo
    {
        return $this->belongsTo(Concepto::class);
    }

    public function alternativas(): HasMany
    {
        return $this->hasMany(Alternativa::class)->orderBy('orden');
    }

    public function alternativaCorrecta(): HasOne
    {
        return $this->hasOne(Alternativa::class)->where('es_correcta', true);
    }
}
