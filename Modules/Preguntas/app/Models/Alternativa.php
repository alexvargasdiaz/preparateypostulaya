<?php

declare(strict_types=1);

namespace Modules\Preguntas\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Alternativa extends Model
{
    protected $table = 'alternativas';

    protected $fillable = [
        'pregunta_id',
        'texto',
        'imagen_url',
        'es_correcta',
        'orden',
    ];

    protected function casts(): array
    {
        return [
            'es_correcta' => 'boolean',
            'orden' => 'integer',
        ];
    }

    public function pregunta(): BelongsTo
    {
        return $this->belongsTo(Pregunta::class);
    }
}
