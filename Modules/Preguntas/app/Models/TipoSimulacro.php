<?php

declare(strict_types=1);

namespace Modules\Preguntas\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TipoSimulacro extends Model
{
    protected $table = 'tipos_simulacro';

    protected $fillable = [
        'area_academica_id',
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

    public function areaAcademica(): BelongsTo
    {
        return $this->belongsTo(AreaAcademica::class);
    }

    public function intentos(): HasMany
    {
        return $this->hasMany(\Modules\Rendicion\Models\IntentoExamen::class);
    }
}
