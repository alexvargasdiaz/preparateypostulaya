<?php

declare(strict_types=1);

namespace Modules\Catalogo\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Preguntas\Models\Concepto;

class RequisitosCarrera extends Model
{
    protected $table = 'requisitos_carrera';

    protected $fillable = [
        'categoria_id',
        'concepto_id',
        'puntaje_minimo',
    ];

    protected function casts(): array
    {
        return [
            'puntaje_minimo' => 'decimal:2',
        ];
    }

    public function categoria(): BelongsTo
    {
        return $this->belongsTo(Categoria::class);
    }

    public function concepto(): BelongsTo
    {
        return $this->belongsTo(Concepto::class);
    }
}
