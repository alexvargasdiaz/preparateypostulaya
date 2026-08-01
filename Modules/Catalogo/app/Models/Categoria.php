<?php

declare(strict_types=1);

namespace Modules\Catalogo\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Categoria extends Model
{
    protected $table = 'categorias';

    protected $fillable = [
        'institucion_id',
        'area_academica_id',
        'nombre',
        'imagen_url',
        'descripcion_corta',
        'puntaje_minimo_total',
        'orden',
        'activo',
    ];

    protected function casts(): array
    {
        return [
            'puntaje_minimo_total' => 'decimal:2',
            'orden' => 'integer',
            'activo' => 'boolean',
        ];
    }

    public function institucion(): BelongsTo
    {
        return $this->belongsTo(Institucion::class);
    }

    public function areaAcademica(): BelongsTo
    {
        return $this->belongsTo(\Modules\Preguntas\Models\AreaAcademica::class);
    }

    public function examenes(): HasMany
    {
        return $this->hasMany(Examen::class);
    }

    public function requisitos(): HasMany
    {
        return $this->hasMany(RequisitosCarrera::class);
    }
}
