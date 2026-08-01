<?php

declare(strict_types=1);

namespace Modules\Preguntas\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AreaAcademica extends Model
{
    protected $table = 'areas_academicas';

    protected $fillable = [
        'nombre',
        'descripcion',
        'num_preguntas',
        'duracion_min',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
        'num_preguntas' => 'integer',
        'duracion_min' => 'integer',
    ];

    public function preguntas(): HasMany
    {
        return $this->hasMany(Pregunta::class, 'area_academica_id');
    }

    public function conceptos(): HasMany
    {
        return $this->hasMany(Concepto::class, 'area_academica_id');
    }

    public function examenes(): HasMany
    {
        return $this->hasMany(\Modules\Catalogo\Models\Examen::class, 'area_academica_id');
    }

    public function tiposSimulacro(): HasMany
    {
        return $this->hasMany(TipoSimulacro::class);
    }

    public function tiposSimulacroActivos(): HasMany
    {
        return $this->hasMany(TipoSimulacro::class)->where('activo', true);
    }

    public function preguntasActivas(): HasMany
    {
        return $this->hasMany(Pregunta::class, 'area_academica_id')->where('activa', true);
    }

    public function preguntasPorNivel(int $nivel): HasMany
    {
        return $this->preguntasActivas()->where('nivel', $nivel);
    }

    public function totalPreguntas(): int
    {
        return $this->preguntasActivas()->count();
    }
}
