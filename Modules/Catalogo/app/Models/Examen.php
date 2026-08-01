<?php

declare(strict_types=1);

namespace Modules\Catalogo\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Modules\Preguntas\Models\AreaAcademica;
use Modules\Preguntas\Models\Concepto;
use Modules\Preguntas\Models\Pregunta;

class Examen extends Model
{
    protected $table = 'examenes';

    protected $fillable = [
        'categoria_id',
        'area_academica_id',
        'tipo',
        'titulo',
        'descripcion',
        'imagen_url',
        'tiempo_limite_min',
        'intentos_permitidos',
        'puntaje_minimo',
        'num_alternativas_default',
        'preguntas_por_intento',
        'aleatorizar_preguntas',
        'aleatorizar_alternativas',
        'activo',
    ];

    protected function casts(): array
    {
        return [
            'tiempo_limite_min' => 'integer',
            'intentos_permitidos' => 'integer',
            'puntaje_minimo' => 'decimal:2',
            'num_alternativas_default' => 'integer',
            'preguntas_por_intento' => 'integer',
            'aleatorizar_preguntas' => 'boolean',
            'aleatorizar_alternativas' => 'boolean',
            'activo' => 'boolean',
        ];
    }

    /**
     * Scope: exámenes diagnósticos.
     */
    public function scopeDiagnosticos($query)
    {
        return $query->where('tipo', 'diagnostico');
    }

    /**
     * Scope: exámenes específicos (por carrera).
     */
    public function scopeEspecificos($query)
    {
        return $query->where('tipo', 'especifico');
    }

    public function esDiagnostico(): bool
    {
        return $this->tipo === 'diagnostico';
    }

    public function categoria(): BelongsTo
    {
        return $this->belongsTo(Categoria::class);
    }

    public function areaAcademica(): BelongsTo
    {
        return $this->belongsTo(AreaAcademica::class);
    }

    public function scopePorArea($query, int $areaId)
    {
        return $query->where('area_academica_id', $areaId);
    }

    public function scopePorTipo($query, string $tipo)
    {
        return $query->where('tipo', $tipo);
    }

    public function secciones(): HasMany
    {
        return $this->hasMany(Seccion::class);
    }

    public function conceptos(): BelongsToMany
    {
        return $this->belongsToMany(Concepto::class, 'examen_concepto')
            ->withPivot('num_preguntas')
            ->withTimestamps();
    }

    public function preguntas(): HasManyThrough
    {
        return $this->hasManyThrough(
            Pregunta::class,
            Concepto::class,
            'area_academica_id',
            'concepto_id',
            'area_academica_id',
            'id'
        );
    }

    public function preguntasDelExamen(): HasMany
    {
        return $this->hasMany(Pregunta::class, 'examen_id');
    }

    public function preguntasActivas()
    {
        return $this->preguntas()->where('activa', true);
    }
}
