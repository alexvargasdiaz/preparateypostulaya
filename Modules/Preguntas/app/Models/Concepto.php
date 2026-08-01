<?php

declare(strict_types=1);

namespace Modules\Preguntas\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Catalogo\Models\Examen;

class Concepto extends Model
{
    protected $table = 'conceptos';

    protected $fillable = [
        'seccion_id',
        'area_academica_id',
        'nivel',
        'nombre',
        'descripcion',
    ];

    protected function casts(): array
    {
        return [
            'nivel' => 'integer',
        ];
    }

    public function areaAcademica(): BelongsTo
    {
        return $this->belongsTo(AreaAcademica::class);
    }

    public function seccion(): BelongsTo
    {
        return $this->belongsTo(\Modules\Catalogo\Models\Seccion::class);
    }

    public function mensajesAyuda(): HasMany
    {
        return $this->hasMany(MensajeAyuda::class);
    }

    public function preguntas(): HasMany
    {
        return $this->hasMany(Pregunta::class);
    }

    public function examenes(): BelongsToMany
    {
        return $this->belongsToMany(Examen::class, 'examen_concepto')
            ->withPivot('num_preguntas')
            ->withTimestamps();
    }

    public function preguntasActivas(): HasMany
    {
        return $this->hasMany(Pregunta::class)->where('activa', true);
    }
}
