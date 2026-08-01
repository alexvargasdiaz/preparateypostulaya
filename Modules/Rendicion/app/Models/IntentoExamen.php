<?php

declare(strict_types=1);

namespace Modules\Rendicion\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Catalogo\Models\Examen;

class IntentoExamen extends Model
{
    protected $table = 'intentos_examen';

    protected $fillable = [
        'usuario_id',
        'institucion_id',
        'carrera',
        'area_academica_id',
        'tipo_simulacro_id',
        'examen_id',
        'estado',
        'token_acceso',
        'email_enviado',
        'whatsapp_solicitado',
        'fecha_inicio',
        'fecha_fin',
        'puntaje_total',
        'puntaje_maximo',
        'aprobado',
        'tiempo_empleado_seg',
        'progreso_guardado',
    ];

    protected function casts(): array
    {
        return [
            'email_enviado' => 'boolean',
            'whatsapp_solicitado' => 'boolean',
            'fecha_inicio' => 'datetime',
            'fecha_fin' => 'datetime',
            'puntaje_total' => 'integer',
            'puntaje_maximo' => 'integer',
            'aprobado' => 'boolean',
            'tiempo_empleado_seg' => 'decimal:2',
            'progreso_guardado' => 'json',
        ];
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function institucion(): BelongsTo
    {
        return $this->belongsTo(\Modules\Catalogo\Models\Institucion::class);
    }

    public function examen(): BelongsTo
    {
        return $this->belongsTo(Examen::class);
    }

    public function areaAcademica(): BelongsTo
    {
        return $this->belongsTo(\Modules\Preguntas\Models\AreaAcademica::class);
    }

    public function tipoSimulacro(): BelongsTo
    {
        return $this->belongsTo(\Modules\Preguntas\Models\TipoSimulacro::class);
    }

    public function respuestas(): HasMany
    {
        return $this->hasMany(RespuestaUsuario::class, 'intento_id');
    }

    public function resultadosConceptos(): HasMany
    {
        return $this->hasMany(ResultadoConcepto::class, 'intento_id');
    }
}
