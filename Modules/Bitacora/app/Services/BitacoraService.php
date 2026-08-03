<?php

declare(strict_types=1);

namespace Modules\Bitacora\Services;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Modules\Catalogo\Models\Institucion;
use Modules\Preguntas\Models\AreaAcademica;
use Modules\Preguntas\Models\Concepto;
use Modules\Preguntas\Models\DiagnosticoConcepto;
use Modules\Rendicion\Models\IntentoExamen;
use Modules\Rendicion\Models\ResultadoConcepto;

/**
 * Bitácora de procesos: estadísticas en vivo calculadas sobre los datos
 * existentes de la aplicación para que el administrador detecte datos
 * incompletos o incorrectos en cada proceso.
 *
 * Acepta un rango de fechas (desde/hasta) para acotar la actividad
 * (registros de alumnos e intentos de examen) a un período concreto.
 */
class BitacoraService
{
    private ?Carbon $desde = null;

    private ?Carbon $hasta = null;

    /**
     * Devuelve toda la información que consume la página de bitácora.
     *
     * @param  string|null  $desde  Fecha inicial (Y-m-d), inclusive.
     * @param  string|null  $hasta  Fecha final (Y-m-d), inclusive.
     *
     * Nota: el rango se guarda en propiedades de instancia para las consultas
     * privadas. Es seguro porque el contenedor resuelve una instancia nueva por
     * petición/test y cada llamada reasigna ambos valores.
     */
    public function obtenerDatos(?string $desde = null, ?string $hasta = null): array
    {
        $this->desde = $desde ? Carbon::parse($desde)->startOfDay() : null;
        $this->hasta = $hasta ? Carbon::parse($hasta)->endOfDay() : null;

        return [
            'kpis' => $this->kpis(),
            'embudo' => $this->embudoProcesos(),
            'registro' => $this->procesoRegistro(),
            'diagnostico' => $this->procesoDiagnostico(),
            'areas' => $this->procesoAreasAcademicas(),
            'universidades' => $this->procesoUniversidades(),
            'resultados' => $this->procesoResultados(),
            'actividadReciente' => $this->actividadReciente(),
            'fechaActualizacion' => now()->format('d/m/Y H:i'),
        ];
    }

    /**
     * Indicadores globales del sistema (acotados al rango de fechas).
     */
    private function kpis(): array
    {
        $estudiantes = $this->aplicarRango(User::where('rol', 'estudiante'));
        $intentos = $this->aplicarRango(IntentoExamen::query());

        return [
            'alumnos' => (clone $estudiantes)->count(),
            'alumnos_aprobados' => (clone $estudiantes)->where('estado', 'activo')->count(),
            'alumnos_pendientes' => (clone $estudiantes)->where('estado', 'pendiente')->count(),
            'alumnos_rechazados' => (clone $estudiantes)->where('estado', 'rechazado')->count(),
            'intentos_total' => (clone $intentos)->count(),
            'intentos_completados' => (clone $intentos)->where('estado', 'completado')->count(),
            'intentos_en_curso' => (clone $intentos)->where('estado', 'en_curso')->count(),
            'intentos_abandonados' => (clone $intentos)->where('estado', 'abandonado')->count(),
            'diagnosticos_completados' => IntentoExamen::query()
                ->tap(fn (Builder $q) => $this->esDiagnostico($q))
                ->where('estado', 'completado')
                ->tap(fn (Builder $q) => $this->aplicarRango($q))
                ->count(),
            'simulacros_completados' => IntentoExamen::query()
                ->tap(fn (Builder $q) => $this->esSimulacro($q))
                ->where('estado', 'completado')
                ->tap(fn (Builder $q) => $this->aplicarRango($q))
                ->count(),
            'emails_enviados' => (clone $intentos)->where('email_enviado', true)->count(),
            'whatsapp_solicitados' => (clone $intentos)->where('whatsapp_solicitado', true)->count(),
        ];
    }

    /**
     * Embudo de los procesos: de registro hasta simulacro completado.
     * Cuenta alumnos únicos en cada etapa (un intento = un alumno activo en esa etapa).
     */
    private function embudoProcesos(): array
    {
        $registrados = $this->aplicarRango(User::where('rol', 'estudiante'))->count();
        $aprobados = $this->aplicarRango(User::where('rol', 'estudiante')->where('estado', 'activo'))->count();

        $stages = [
            [
                'id' => 'registrados',
                'label' => 'Alumnos registrados',
                'valor' => $registrados,
            ],
            [
                'id' => 'aprobados',
                'label' => 'Alumnos aprobados',
                'valor' => $aprobados,
            ],
            [
                'id' => 'diagnostico_iniciado',
                'label' => 'Iniciaron diagnóstico',
                'valor' => $this->alumnosUnicos(fn (Builder $q) => $this->esDiagnostico($q)),
            ],
            [
                'id' => 'diagnostico_completado',
                'label' => 'Completaron diagnóstico',
                'valor' => $this->alumnosUnicos(fn (Builder $q) => $this->esDiagnostico($q)->where('estado', 'completado')),
            ],
            [
                'id' => 'simulacro_completado',
                'label' => 'Completaron simulacro',
                'valor' => $this->alumnosUnicos(fn (Builder $q) => $this->esSimulacro($q)->where('estado', 'completado')),
            ],
        ];

        $total = $stages[0]['valor'];
        foreach ($stages as $i => &$stage) {
            $previo = $i === 0 ? $total : $stages[$i - 1]['valor'];
            $stage['conversion'] = $previo > 0 ? (int) round(($stage['valor'] / $previo) * 100) : 0;
            $stage['conversion_total'] = $total > 0 ? (int) round(($stage['valor'] / $total) * 100) : 0;
        }
        unset($stage);

        return $stages;
    }

    /**
     * Proceso 1: Registro y aprobación de alumnos.
     */
    private function procesoRegistro(): array
    {
        $porEstado = $this->aplicarRango(User::where('rol', 'estudiante'))
            ->selectRaw('estado, count(*) as total')
            ->groupBy('estado')
            ->pluck('total', 'estado');

        $pendientes = (int) $porEstado->get('pendiente', 0);
        $aprobados = (int) $porEstado->get('activo', 0);
        $rechazados = (int) $porEstado->get('rechazado', 0);
        $total = $pendientes + $aprobados + $rechazados;

        return [
            'por_estado' => [
                ['estado' => 'pendiente', 'label' => 'Pendientes', 'total' => $pendientes],
                ['estado' => 'activo', 'label' => 'Aprobados', 'total' => $aprobados],
                ['estado' => 'rechazado', 'label' => 'Rechazados', 'total' => $rechazados],
            ],
            'tasa_aprobacion' => $total > 0 ? (int) round(($aprobados / $total) * 100) : 0,
            'ultimos' => $this->aplicarRango(User::where('rol', 'estudiante'))
                ->orderBy('created_at', 'desc')
                ->limit(6)
                ->get(['id', 'name', 'email', 'estado', 'created_at'])
                ->map(fn (User $u) => [
                    'id' => $u->id,
                    'name' => $u->name,
                    'email' => $u->email,
                    'estado' => $u->estado,
                    'fecha' => $u->created_at?->format('d/m/Y H:i'),
                ]),
        ];
    }

    /**
     * Proceso 2: Diagnóstico (configuración + uso).
     */
    private function procesoDiagnostico(): array
    {
        $intentosDiag = $this->aplicarRango(IntentoExamen::query())
            ->tap(fn (Builder $q) => $this->esDiagnostico($q));
        $config = DiagnosticoConcepto::query();

        return [
            'config' => [
                'conceptos_configurados' => (clone $config)->count(),
                'conceptos_totales' => Concepto::count(),
                'promedio_preguntas_por_concepto' => $this->promedioPreguntasPorConcepto($config),
                'duracion_minutos' => (clone $config)->first()?->duracion_minutos,
            ],
            'intentos' => [
                'iniciados' => (clone $intentosDiag)->count(),
                'en_curso' => (clone $intentosDiag)->where('estado', 'en_curso')->count(),
                'completados' => (clone $intentosDiag)->where('estado', 'completado')->count(),
                'abandonados' => (clone $intentosDiag)->where('estado', 'abandonado')->count(),
                'aprobados' => (clone $intentosDiag)->where('estado', 'completado')->where('aprobado', true)->count(),
                'promedio_puntaje' => $this->promedioPuntaje($intentosDiag),
            ],
            'alumnos_con_diagnostico' => $this->alumnosUnicos(fn (Builder $q) => $this->esDiagnostico($q)),
        ];
    }

    /**
     * Proceso 3: Simulacros por área académica.
     *
     * @return array<int, array<string, mixed>>
     */
    private function procesoAreasAcademicas(): array
    {
        $areas = AreaAcademica::orderBy('nombre')->get(['id', 'nombre', 'activo']);

        return $areas->map(function (AreaAcademica $area) {
            $q = $this->aplicarRango(IntentoExamen::where('area_academica_id', $area->id));
            $completados = (clone $q)->where('estado', 'completado');

            return [
                'area_id' => $area->id,
                'nombre' => $area->nombre,
                'activo' => (bool) $area->activo,
                'intentos' => (clone $q)->count(),
                'completados' => (clone $completados)->count(),
                'aprobados' => (clone $completados)->where('aprobado', true)->count(),
                'promedio' => $this->promedioPuntaje($q),
            ];
        })->values()->all();
    }

    /**
     * Proceso 4: Simulacros por universidad / carrera postulada.
     *
     * @return array<string, mixed>
     */
    private function procesoUniversidades(): array
    {
        $instituciones = Institucion::orderBy('nombre')->get(['id', 'nombre']);

        $porInstitucion = $instituciones->map(function (Institucion $inst) {
            $q = $this->aplicarRango(IntentoExamen::where('institucion_id', $inst->id));
            $completados = (clone $q)->where('estado', 'completado');

            return [
                'institucion_id' => $inst->id,
                'nombre' => $inst->nombre,
                'intentos' => (clone $q)->count(),
                'completados' => (clone $completados)->count(),
                'aprobados' => (clone $completados)->where('aprobado', true)->count(),
                'carreras' => (clone $q)->whereNotNull('carrera')->distinct('carrera')->count('carrera'),
                'promedio' => $this->promedioPuntaje($q),
            ];
        })->values()->all();

        // Carreras más simuladas
        $topCarreras = $this->aplicarRango(IntentoExamen::whereNotNull('carrera'))
            ->selectRaw("
                carrera,
                count(*) as intentos,
                sum(case when estado = 'completado' then 1 else 0 end) as completados,
                sum(case when aprobado then 1 else 0 end) as aprobados
            ")
            ->groupBy('carrera')
            ->orderByDesc('intentos')
            ->orderBy('carrera')
            ->limit(8)
            ->get()
            ->map(fn ($row) => [
                'carrera' => $row->carrera,
                'intentos' => (int) $row->intentos,
                'completados' => (int) $row->completados,
                'aprobados' => (int) $row->aprobados,
            ])
            ->all();

        return [
            'por_institucion' => $porInstitucion,
            'top_carreras' => $topCarreras,
        ];
    }

    /**
     * Proceso 5: Resultados y envíos.
     *
     * @return array<string, mixed>
     */
    private function procesoResultados(): array
    {
        $completados = $this->aplicarRango(IntentoExamen::where('estado', 'completado'));
        $total = (clone $completados)->count();
        $aprobados = (clone $completados)->where('aprobado', true)->count();

        return [
            'completados' => $total,
            'aprobados' => $aprobados,
            'desaprobados' => max(0, $total - $aprobados),
            'promedio_global' => $this->promedioPuntaje($this->aplicarRango(IntentoExamen::query())),
            'resultados_concepto' => [
                'registros' => $this->aplicarRango(ResultadoConcepto::query())->count(),
                'intentos_con_resultados' => $this->aplicarRango(ResultadoConcepto::query())->distinct('intento_id')->count('intento_id'),
                'promedio_acierto' => (int) round((float) ($this->aplicarRango(ResultadoConcepto::query())->avg('porcentaje_acierto') ?? 0)),
            ],
            'envios' => [
                'emails' => $this->aplicarRango(IntentoExamen::where('email_enviado', true))->count(),
                'whatsapp' => $this->aplicarRango(IntentoExamen::where('whatsapp_solicitado', true))->count(),
            ],
        ];
    }

    /**
     * Bitácora de actividad reciente: últimos intentos y últimos registros.
     *
     * @return array<string, mixed>
     */
    private function actividadReciente(): array
    {
        $intentos = $this->aplicarRango(IntentoExamen::with(['usuario:id,name', 'institucion:id,nombre', 'areaAcademica:id,nombre', 'examen:id,titulo']))
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get()
            ->map(function (IntentoExamen $i) {
                if ($i->examen_id) {
                    // Simulacro de área (iniciarIntento) — puede arrastrar institucion_id de su categoría
                    $tipo = 'area';
                    $referencia = $i->areaAcademica?->nombre ?? $i->examen?->titulo ?? 'Simulacro';
                } elseif ($i->tipo_simulacro_id || $i->institucion_id || $i->categoria_id) {
                    // Simulacro por universidad + carrera postulada (iniciarIntentoUniversidad)
                    $tipo = 'universidad';
                    $referencia = ($i->institucion?->nombre ?? 'Universidad')
                        . ($i->carrera ? " · {$i->carrera}" : '');
                } else {
                    $tipo = 'diagnostico';
                    $referencia = 'Diagnóstico general';
                }

                return [
                    'id' => $i->id,
                    'usuario' => $i->usuario?->name ?? 'Invitado',
                    'tipo' => $tipo,
                    'referencia' => $referencia,
                    'estado' => $i->estado,
                    'aprobado' => $i->aprobado,
                    'puntaje' => ($i->puntaje_total !== null && $i->puntaje_maximo > 0)
                        ? (int) round(($i->puntaje_total / $i->puntaje_maximo) * 100)
                        : null,
                    'fecha' => $i->created_at?->format('d/m/Y H:i'),
                ];
            })
            ->all();

        $registros = $this->aplicarRango(User::where('rol', 'estudiante'))
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get(['id', 'name', 'email', 'estado', 'created_at'])
            ->map(fn (User $u) => [
                'id' => $u->id,
                'name' => $u->name,
                'email' => $u->email,
                'estado' => $u->estado,
                'fecha' => $u->created_at?->format('d/m/Y H:i'),
            ])
            ->all();

        return ['intentos' => $intentos, 'registros' => $registros];
    }

    /**
     * Promedio de preguntas por concepto de la configuración del diagnóstico.
     */
    private function promedioPreguntasPorConcepto(Builder $config): ?int
    {
        $valores = (clone $config)->pluck('preguntas_por_concepto')->filter(fn ($v) => $v !== null);

        if ($valores->isEmpty()) {
            return null;
        }

        return (int) round((float) $valores->avg());
    }

    /**
     * Promedio de porcentaje de acierto de los intentos completados de una consulta.
     */
    private function promedioPuntaje(Builder $query): ?int
    {
        $rows = (clone $query)
            ->where('estado', 'completado')
            ->whereNotNull('puntaje_total')
            ->where('puntaje_maximo', '>', 0)
            ->get(['puntaje_total', 'puntaje_maximo']);

        if ($rows->isEmpty()) {
            return null;
        }

        $suma = $rows->sum(fn ($r) => ($r->puntaje_total / $r->puntaje_maximo) * 100);

        return (int) round($suma / $rows->count());
    }

    /**
     * Aplica el rango de fechas (desde/hasta) a una consulta sobre la columna
     * created_at. Si no hay rango, no modifica la consulta.
     */
    private function aplicarRango(Builder $query): Builder
    {
        if ($this->desde !== null) {
            $query->where('created_at', '>=', $this->desde);
        }

        if ($this->hasta !== null) {
            $query->where('created_at', '<=', $this->hasta);
        }

        return $query;
    }

    /**
     * Filtro de intentos que pertenecen al proceso de diagnóstico
     * (los simulacros de área usan examen_id y los de universidad usan
     * institucion_id / tipo_simulacro_id / categoria_id).
     */
    private function esDiagnostico(Builder $query): Builder
    {
        return $query->whereNull('examen_id')
            ->whereNull('institucion_id')
            ->whereNull('tipo_simulacro_id')
            ->whereNull('categoria_id');
    }

    /**
     * Filtro de intentos que pertenecen a un simulacro (área o universidad).
     */
    private function esSimulacro(Builder $query): Builder
    {
        return $query->where(function (Builder $s) {
            $s->whereNotNull('examen_id')
                ->orWhereNotNull('institucion_id')
                ->orWhereNotNull('tipo_simulacro_id');
        });
    }

    /**
     * Cantidad de alumnos únicos con al menos un intento que cumple la condición.
     */
    private function alumnosUnicos(callable $condicion): int
    {
        return (int) $this->aplicarRango(IntentoExamen::query())
            ->whereNotNull('usuario_id')
            ->tap($condicion)
            ->distinct('usuario_id')
            ->count('usuario_id');
    }
}
