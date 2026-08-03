<?php

declare(strict_types=1);

namespace Modules\Examenes\Services;

use Modules\Catalogo\Models\Examen;
use Modules\Catalogo\Models\Categoria;
use Modules\Catalogo\Models\Institucion;
use Modules\Preguntas\Models\Alternativa;
use Modules\Preguntas\Models\AreaAcademica;
use Modules\Preguntas\Models\Concepto;
use Modules\Preguntas\Models\Pregunta;
use Modules\Preguntas\Models\TipoSimulacro;
use Modules\Rendicion\Models\IntentoExamen;
use Modules\Rendicion\Models\RespuestaUsuario;
use Modules\Rendicion\Models\ResultadoConcepto;

class ExamenService
{
    public function iniciarIntento(Examen $examen): IntentoExamen
    {
        $preguntasIds = [];

        // 1) Seleccionar preguntas por concepto según la configuración del examen
        $conceptos = $examen->conceptos()->withPivot('num_preguntas')->get();

        foreach ($conceptos as $concepto) {
            $numPreguntas = $concepto->pivot->num_preguntas;

            $ids = Pregunta::where('concepto_id', $concepto->id)
                ->where('activa', true)
                ->inRandomOrder()
                ->limit($numPreguntas)
                ->pluck('id')
                ->toArray();

            $preguntasIds = array_merge($preguntasIds, $ids);
        }

        // 2) Si no hay conceptos configurados, tomar las preguntas directas del examen
        if (empty($preguntasIds)) {
            $preguntasPorIntento = $examen->preguntas_por_intento ?? 10;
            $preguntasIds = Pregunta::where('examen_id', $examen->id)
                ->where('activa', true)
                ->inRandomOrder()
                ->limit($preguntasPorIntento)
                ->pluck('id')
                ->toArray();
        }

        // 3) Fallback: tomar preguntas del área académica
        if (empty($preguntasIds) && $examen->area_academica_id) {
            $preguntasPorIntento = $examen->preguntas_por_intento ?? 10;
            $preguntasIds = Pregunta::where('area_academica_id', $examen->area_academica_id)
                ->where('activa', true)
                ->inRandomOrder()
                ->limit($preguntasPorIntento)
                ->pluck('id')
                ->toArray();
        }

        return IntentoExamen::create([
            'usuario_id' => auth()->id(),
            'examen_id' => $examen->id,
            'area_academica_id' => $examen->area_academica_id,
            'carrera' => $examen->areaAcademica?->nombre ?? $examen->categoria?->nombre,
            'institucion_id' => $examen->categoria?->institucion_id,
            'estado' => 'en_curso',
            'fecha_inicio' => now(),
            'puntaje_maximo' => count($preguntasIds),
            'progreso_guardado' => [
                'preguntas_ids' => $preguntasIds,
            ],
        ]);
    }

    /**
     * Selecciona preguntas para un simulacro por universidad + área académica.
     *
     * Prioriza el banco propio de la universidad (institucion_id = X) para el
     * área seleccionada y completa con el banco global del área si las propias
     * no alcanzan el número solicitado. Si la universidad no tiene preguntas
     * propias, usa solo el banco global.
     */
    public function seleccionarPreguntasUniversidad(
        int $areaId,
        int $institucionId,
        int $numPreguntas
    ): array {
        $propias = Pregunta::where('area_academica_id', $areaId)
            ->where('institucion_id', $institucionId)
            ->where('activa', true)
            ->inRandomOrder()
            ->limit($numPreguntas)
            ->pluck('id')
            ->toArray();

        if (count($propias) < $numPreguntas) {
            $faltantes = $numPreguntas - count($propias);
            $globales = Pregunta::where('area_academica_id', $areaId)
                ->whereNull('institucion_id')
                ->where('activa', true)
                ->whereNotIn('id', $propias)
                ->inRandomOrder()
                ->limit($faltantes)
                ->pluck('id')
                ->toArray();

            $propias = array_merge($propias, $globales);
        }

        return $propias;
    }

    /**
     * Crea un intento de simulacro vinculado a universidad + área académica
     * y a la carrera (categoría) a la que postula el alumno.
     */
    public function iniciarIntentoUniversidad(
        Institucion $institucion,
        AreaAcademica $area,
        TipoSimulacro $tipo,
        Categoria $categoria
    ): IntentoExamen {
        $preguntasIds = $this->seleccionarPreguntasUniversidad(
            areaId: $area->id,
            institucionId: $institucion->id,
            numPreguntas: $tipo->num_preguntas,
        );

        return IntentoExamen::create([
            'usuario_id' => auth()->id(),
            'institucion_id' => $institucion->id,
            'categoria_id' => $categoria->id,
            'area_academica_id' => $area->id,
            'tipo_simulacro_id' => $tipo->id,
            'carrera' => $categoria->nombre,
            'estado' => 'en_curso',
            'fecha_inicio' => now(),
            'puntaje_maximo' => count($preguntasIds),
            'puntaje_total' => 0,
            'progreso_guardado' => [
                'preguntas_ids' => $preguntasIds,
                'tipo_simulacro' => $tipo->nombre,
            ],
        ]);
    }

    /**
     * Guarda varias respuestas de un intento en una sola operación.
     *
     * Reemplaza el patrón anterior de "una petición HTTP por respuesta", que
     * al finalizar un examen con muchas preguntas (p. ej. el diagnóstico con
     * 200+ preguntas) disparaba cientos de requests en paralelo y bloqueaba la
     * pantalla de resultados. Con este método, el front envía lotes de
     * respuestas en una única llamada (upsert sobre la PK natural
     * intento_id + pregunta_id).
     *
     * @param  array<int, array{pregunta_id: int, alternativa_id_elegida: int|null}>  $respuestas
     */
    public function guardarRespuestasMasivas(IntentoExamen $intento, array $respuestas): int
    {
        $alternativaIds = collect($respuestas)
            ->pluck('alternativa_id_elegida')
            ->filter()
            ->unique()
            ->values();

        $correctas = Alternativa::whereIn('id', $alternativaIds)->pluck('es_correcta', 'id');

        $ahora = now();
        $filas = array_map(fn ($r) => [
            'intento_id' => $intento->id,
            'pregunta_id' => $r['pregunta_id'],
            'alternativa_id_elegida' => $r['alternativa_id_elegida'],
            'es_correcta' => $r['alternativa_id_elegida']
                ? ($correctas->get($r['alternativa_id_elegida']) ?? false)
                : null,
            'created_at' => $ahora,
            'updated_at' => $ahora,
        ], $respuestas);

        RespuestaUsuario::upsert(
            $filas,
            ['intento_id', 'pregunta_id'],
            ['alternativa_id_elegida', 'es_correcta', 'updated_at'],
        );

        return count($respuestas);
    }

    public function calcularPuntaje(IntentoExamen $intento): void
    {
        $preguntasIds = $intento->progreso_guardado['preguntas_ids'] ?? [];

        if (!empty($preguntasIds)) {
            $totalPreguntas = count($preguntasIds);
        } else {
            $totalPreguntas = Pregunta::where('area_academica_id', $intento->area_academica_id)
                ->where('activa', true)
                ->count();
        }

        $correctas = $intento->respuestas->where('es_correcta', true)->count();
        $puntajeMinimo = round($totalPreguntas * 0.6);

        $intento->update([
            'puntaje_total' => $correctas,
            'puntaje_maximo' => $totalPreguntas,
            'aprobado' => $correctas >= $puntajeMinimo,
        ]);
    }

    public function calcularResultadosPorConcepto(IntentoExamen $intento): void
    {
        $preguntas = Pregunta::whereIn('id', $intento->respuestas->pluck('pregunta_id'))
            ->with('concepto')
            ->get()
            ->keyBy('id');

        $conceptos = [];
        foreach ($intento->respuestas as $respuesta) {
            $pregunta = $preguntas->get($respuesta->pregunta_id);
            if (!$pregunta?->concepto_id) continue;

            $conceptos[$pregunta->concepto_id] ??= [
                'concepto_id' => $pregunta->concepto_id,
                'preguntas_totales' => 0,
                'preguntas_correctas' => 0,
            ];
            $conceptos[$pregunta->concepto_id]['preguntas_totales']++;
            if ($respuesta->es_correcta) {
                $conceptos[$pregunta->concepto_id]['preguntas_correctas']++;
            }
        }

        foreach ($conceptos as $data) {
            $pct = $data['preguntas_totales'] > 0
                ? round(($data['preguntas_correctas'] / $data['preguntas_totales']) * 100, 2)
                : 0;

            ResultadoConcepto::updateOrCreate(
                ['intento_id' => $intento->id, 'concepto_id' => $data['concepto_id']],
                [
                    'preguntas_totales' => $data['preguntas_totales'],
                    'preguntas_correctas' => $data['preguntas_correctas'],
                    'porcentaje_acierto' => $pct,
                ]
            );
        }
    }
}
