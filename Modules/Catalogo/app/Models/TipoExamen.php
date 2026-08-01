<?php

declare(strict_types=1);

namespace Modules\Catalogo\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TipoExamen extends Model
{
    protected $table = 'tipos_examen';

    protected $fillable = [
        'nombre',
        'slug',
        'descripcion',
        'activo',
    ];

    protected function casts(): array
    {
        return [
            'activo' => 'boolean',
        ];
    }

    public function instituciones(): HasMany
    {
        return $this->hasMany(Institucion::class);
    }
}
