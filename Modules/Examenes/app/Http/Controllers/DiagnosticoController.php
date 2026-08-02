<?php

declare(strict_types=1);

namespace Modules\Examenes\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Catalogo\Models\Categoria;
use Modules\Catalogo\Models\RequisitosCarrera;
use Modules\Preguntas\Models\AreaAcademica;
use Modules\Preguntas\Models\Concepto;
use Modules\Preguntas\Models\Alternativa;
use Modules\Preguntas\Models\DiagnosticoConcepto;
use Modules\Preguntas\Models\Pregunta;
use Modules\Rendicion\Models\IntentoExamen;
use Modules\Rendicion\Models\RespuestaUsuario;
use Modules\Rendicion\Models\ResultadoConcepto;

class DiagnosticoController extends Controller
{
    /**
     * Muestra la información del examen diagnóstico y el historial.
     */
    public function index(): Response
    {
        $user = auth()->user();

        $intentos = IntentoExamen::where('usuario_id', $user->id)
            ->where('examen_id', null)
            ->whereNotNull('fecha_fin')
            ->orderBy('created_at', 'desc')
            ->get();

        $configMap = DiagnosticoConcepto::pluck('preguntas_por_concepto', 'concepto_id');

        $areas = AreaAcademica::where('activo', true)
            ->with(['conceptos' => fn ($q) => $q->whereIn('id', $configMap->keys())->orderBy('nombre')->withCount(['preguntasActivas as preguntas_count'])])
            ->orderBy('nombre')
            ->withCount(['conceptos' => fn ($q) => $q->whereIn('id', $configMap->keys())])
            ->get(['id', 'nombre']);

        $totalPreguntas = 0;
        foreach ($areas as $area) {
            foreach ($area->conceptos as $c) {
                $limite = $configMap->get($c->id, 10);
                $totalPreguntas += min($c->preguntas_count, $limite);
            }
        }

        // Duración: configurada o automática (~30 seg por pregunta, mínimo 5 min)
        $duracionConfig = DiagnosticoConcepto::first()?->duracion_minutos;
        $duracionMinutos = $duracionConfig
            ? (int) $duracionConfig
            : max(5, (int) ceil($configMap->sum() / 2));

        return Inertia::render('Diagnosticos/Index', [
            'intentos' => $intentos,
            'areas' => $areas,
            'totalPreguntas' => $totalPreguntas,
            'duracionMinutos' => $duracionMinutos,
        ]);
    }

    /**
     * Inicia un nuevo examen diagnóstico: selecciona preguntas de todas las áreas.
     */
    public function iniciar(): Response|RedirectResponse
    {
        $user = auth()->user();

        $intentoActivo = IntentoExamen::where('usuario_id', $user->id)
            ->where('examen_id', null)
            ->where('estado', 'en_curso')
            ->first();

        if ($intentoActivo) {
            return redirect()->route('diagnostico.rendir', ['intento' => $intentoActivo->id]);
        }

        $configMap = DiagnosticoConcepto::pluck('preguntas_por_concepto', 'concepto_id');

        if ($configMap->isEmpty()) {
            return back()->with('error', 'No hay cursos configurados para el diagnóstico. El administrador debe configurarlos primero.');
        }

        $areas = AreaAcademica::where('activo', true)
            ->with(['conceptos' => fn ($q) => $q->whereIn('id', $configMap->keys())->withCount(['preguntasActivas as preguntas_count'])])
            ->get();

        if ($areas->isEmpty()) {
            return back()->with('error', 'No hay áreas disponibles para el diagnóstico.');
        }

        $preguntasSeleccionadas = collect();
        $preguntasPorArea = [];

        foreach ($areas as $area) {
            $preguntasArea = collect();
            foreach ($area->conceptos as $concepto) {
                $limite = min($configMap->get($concepto->id, 10), $concepto->preguntas_count);
                if ($limite === 0) continue;

                $preguntas = Pregunta::where('concepto_id', $concepto->id)
                    ->where('activa', true)
                    ->inRandomOrder()
                    ->limit($limite)
                    ->get();

                $preguntasArea = $preguntasArea->concat($preguntas);
            }
            if ($preguntasArea->isNotEmpty()) {
                $preguntasPorArea[$area->id] = [
                    'area_id' => $area->id,
                    'area_nombre' => $area->nombre,
                    'preguntas_ids' => $preguntasArea->pluck('id')->toArray(),
                ];
                $preguntasSeleccionadas = $preguntasSeleccionadas->concat($preguntasArea);
            }
        }

        if ($preguntasSeleccionadas->isEmpty()) {
            return back()->with('error', 'No hay suficientes preguntas para generar el diagnóstico.');
        }

        $idsSeleccionados = $preguntasSeleccionadas->pluck('id')->toArray();

        // Calcular duración: usar la configurada o auto (~30 seg por pregunta, mínimo 5 min)
        $duracionConfig = DiagnosticoConcepto::first()?->duracion_minutos;
        $tiempoTotal = $duracionConfig
            ? $duracionConfig * 60
            : max(5, (int) ceil(count($idsSeleccionados) / 2)) * 60;

        $intento = IntentoExamen::create([
            'usuario_id' => $user->id,
            'examen_id' => null,
            'institucion_id' => null,
            'carrera' => null,
            'estado' => 'en_curso',
            'fecha_inicio' => now(),
            'progreso_guardado' => [
                'preguntas_ids' => $idsSeleccionados,
                'tipo' => 'diagnostico',
                'areas' => $preguntasPorArea,
                'tiempo_total_seg' => $tiempoTotal,
            ],
        ]);

        return redirect()->route('diagnostico.rendir', ['intento' => $intento->id]);
    }

    /**
     * Muestra la pantalla de rendición del diagnóstico.
     */
    public function rendir($intentoId): Response
    {
        $intento = IntentoExamen::findOrFail($intentoId);

        if ($intento->usuario_id !== auth()->id()) {
            abort(403);
        }

        $preguntasIds = $intento->progreso_guardado['preguntas_ids'] ?? [];

        $preguntas = Pregunta::with(['alternativas' => fn ($q) => $q->inRandomOrder(), 'concepto'])
            ->whereIn('id', $preguntasIds)
            ->where('activa', true)
            ->inRandomOrder()
            ->get();

        $respuestasGuardadas = RespuestaUsuario::where('intento_id', $intento->id)
            ->get()
            ->keyBy('pregunta_id');

        $preguntasParaFrontend = $preguntas->map(function ($pregunta) use ($respuestasGuardadas) {
            $data = $pregunta->toArray();
            $data['alternativas'] = $pregunta->alternativas->map(function ($alt) {
                $a = $alt->toArray();
                unset($a['es_correcta']);
                return $a;
            });
            $data['respuesta_guardada'] = $respuestasGuardadas->get($pregunta->id)?->alternativa_id_elegida;
            return $data;
        });

        // Agrupar preguntas por área para mostrar progreso
        $areas = $preguntas->groupBy(fn ($p) => $p->concepto?->nombre ?? 'General')
            ->map(function ($preguntas, $area) {
                return [
                    'nombre' => $area,
                    'total' => $preguntas->count(),
                ];
            })
            ->values();

        // Usar el tiempo almacenado del progreso, o calcular automático
        $tiempoTotal = $intento->progreso_guardado['tiempo_total_seg'] ?? (count($preguntasIds) * 60);

        // Calcular tiempo restante (considerando el tiempo ya transcurrido)
        $tiempoTranscurrido = $intento->fecha_inicio->diffInSeconds(now());
        $tiempoRestante = max(0, $tiempoTotal - $tiempoTranscurrido);

        return Inertia::render('Diagnosticos/Rendir', [
            'intento' => $intento,
            'preguntas' => $preguntasParaFrontend,
            'areas' => $areas,
            'tiempoRestante' => $tiempoRestante,
        ]);
    }

    /**
     * Guarda la respuesta del usuario (auto-save).
     */
    public function guardarRespuesta(Request $request, $intentoId): JsonResponse
    {
        $validated = $request->validate([
            'pregunta_id' => 'required|exists:preguntas,id',
            'alternativa_id_elegida' => 'nullable|exists:alternativas,id',
        ]);

        $intento = IntentoExamen::findOrFail($intentoId);

        if ($intento->usuario_id !== auth()->id()) {
            abort(403);
        }

        if ($intento->estado === 'completado' || $intento->estado === 'abandonado') {
            return response()->json(['error' => 'Este intento ya fue finalizado'], 409);
        }

        $alternativa = $validated['alternativa_id_elegida']
            ? Alternativa::find($validated['alternativa_id_elegida'])
            : null;

        RespuestaUsuario::updateOrCreate(
            ['intento_id' => $intento->id, 'pregunta_id' => $validated['pregunta_id']],
            [
                'alternativa_id_elegida' => $validated['alternativa_id_elegida'],
                'es_correcta' => $alternativa?->es_correcta,
            ]
        );

        return response()->json(['saved' => true]);
    }

    /**
     * Finaliza el diagnóstico, calcula resultados y compatibilidad con carreras.
     */
    public function finalizar($intentoId)
    {
        $intento = IntentoExamen::with(['respuestas'])
            ->findOrFail($intentoId);

        if ($intento->usuario_id !== auth()->id()) {
            abort(403);
        }

        if ($intento->estado === 'completado') {
            return redirect()->route('diagnostico.resultados', ['intento' => $intento->id]);
        }

        $intento->update([
            'estado' => 'completado',
            'fecha_fin' => now(),
            'tiempo_empleado_seg' => $intento->fecha_inicio->diffInSeconds(now()),
        ]);

        // Calcular puntaje general
        $preguntasIds = $intento->progreso_guardado['preguntas_ids'] ?? [];
        $totalPreguntas = count($preguntasIds);
        $correctas = $intento->respuestas->where('es_correcta', true)->count();

        $intento->update([
            'puntaje_total' => $correctas,
            'puntaje_maximo' => $totalPreguntas,
            'aprobado' => $correctas >= round($totalPreguntas * 0.6),
        ]);

        // Calcular resultados por concepto/área
        $this->calcularResultadosPorConcepto($intento);

        return redirect()->route('diagnostico.resultados', ['intento' => $intento->id]);
    }

    /**
     * Muestra los resultados del diagnóstico con compatibilidad por carrera.
     * Agrupado por área académica.
     */
    public function resultados($intentoId): Response
    {
        $intento = IntentoExamen::with(['respuestas', 'resultadosConceptos.concepto.areaAcademica'])
            ->findOrFail($intentoId);

        if ($intento->usuario_id !== auth()->id()) {
            abort(403);
        }

        // Puntaje por concepto (individual)
        $puntajePorConcepto = $intento->resultadosConceptos->map(fn ($rc) => [
            'concepto_id' => $rc->concepto_id,
            'nombre' => $rc->concepto?->nombre ?? 'Sin área',
            'area_id' => $rc->concepto?->areaAcademica?->id,
            'area_nombre' => $rc->concepto?->areaAcademica?->nombre ?? 'General',
            'correctas' => $rc->preguntas_correctas,
            'total' => $rc->preguntas_totales,
            'porcentaje' => $rc->porcentaje_acierto,
        ])->values();

        // Puntaje por área (agrupado)
        $puntajePorArea = $puntajePorConcepto
            ->groupBy('area_id')
            ->map(function ($conceptos, $areaId) {
                $totalCorrectas = $conceptos->sum('correctas');
                $totalPreguntas = $conceptos->sum('total');
                return [
                    'area_id' => (int) $areaId,
                    'nombre' => $conceptos->first()['area_nombre'],
                    'correctas' => $totalCorrectas,
                    'total' => $totalPreguntas,
                    'porcentaje' => $totalPreguntas > 0
                        ? round(($totalCorrectas / $totalPreguntas) * 100, 2)
                        : 0,
                    'conceptos' => $conceptos->map(fn ($c) => [
                        'nombre' => $c['nombre'],
                        'correctas' => $c['correctas'],
                        'total' => $c['total'],
                        'porcentaje' => $c['porcentaje'],
                    ])->values(),
                ];
            })
            ->values();

        // Calcular puntaje total del estudiante (porcentaje)
        $puntajeTotalEstudiante = $intento->puntaje_maximo > 0
            ? round(($intento->puntaje_total / $intento->puntaje_maximo) * 100, 2)
            : 0;

        // Obtener todas las carreras activas
        $categorias = Categoria::with(['institucion', 'requisitos.concepto', 'areaAcademica'])
            ->where('activo', true)
            ->get();

        $compatibles = [];
        $noCompatibles = [];

        foreach ($categorias as $categoria) {
            $requisitos = $categoria->requisitos;
            $minimoTotal = $categoria->puntaje_minimo_total ?? 0;
            $areaCarrera = $categoria->areaAcademica;

            $cumplePorPuntaje = $puntajeTotalEstudiante >= $minimoTotal;

            // Verificar rendimiento en el área específica de la carrera (si tiene área asignada)
            $puntajeAreaCarrera = null;
            $cumpleAreaCarrera = true;
            if ($areaCarrera) {
                $areaData = $puntajePorArea->firstWhere('area_id', $areaCarrera->id);
                $puntajeAreaCarrera = $areaData['porcentaje'] ?? 0;
                // Si el área tiene menos de 60%, no cumple el perfil de la carrera
                $cumpleAreaCarrera = $puntajeAreaCarrera >= 60;
            }

            // Verificar requisitos por concepto si existen
            $cumpleRequisitos = true;
            $areasFaltantes = [];

            foreach ($requisitos as $requisito) {
                $conc = $puntajePorConcepto->firstWhere('concepto_id', $requisito->concepto_id);
                $pctObtenido = $conc['porcentaje'] ?? 0;
                $pctRequerido = $requisito->puntaje_minimo;

                if ($pctObtenido < $pctRequerido) {
                    $cumpleRequisitos = false;
                    $areasFaltantes[] = [
                        'nombre' => $requisito->concepto?->nombre ?? 'Curso',
                        'obtenido' => $pctObtenido,
                        'requerido' => $pctRequerido,
                    ];
                }
            }

            $entry = [
                'categoria_id' => $categoria->id,
                'nombre' => $categoria->nombre,
                'institucion' => $categoria->institucion?->nombre ?? '—',
                'puntaje_obtenido' => $puntajeTotalEstudiante,
                'puntaje_minimo' => $minimoTotal,
                'cumple_puntaje' => $cumplePorPuntaje,
                'area_carrera' => $areaCarrera?->nombre,
                'puntaje_area_carrera' => $puntajeAreaCarrera,
                'cumple_area_carrera' => $cumpleAreaCarrera,
                'total_requisitos' => $requisitos->count(),
            ];

            $esCompatible = $cumplePorPuntaje
                && $cumpleAreaCarrera
                && ($requisitos->isEmpty() || $cumpleRequisitos);

            if ($esCompatible) {
                $compatibles[] = $entry;
            } else {
                $entry['areas_faltantes'] = $areasFaltantes;
                $noCompatibles[] = $entry;
            }
        }

        usort($compatibles, fn ($a, $b) => $b['puntaje_obtenido'] <=> $a['puntaje_obtenido']);
        usort($noCompatibles, fn ($a, $b) => $b['puntaje_obtenido'] <=> $a['puntaje_obtenido']);

        return Inertia::render('Diagnosticos/Resultados', [
            'intento' => $intento,
            'puntajePorArea' => $puntajePorArea,
            'puntajePorConcepto' => $puntajePorConcepto,
            'puntajeTotalEstudiante' => $puntajeTotalEstudiante,
            'carrerasCompatibles' => $compatibles,
            'carrerasNoCompatibles' => $noCompatibles,
        ]);
    }

    /**
     * Calcula los resultados desglosados por concepto/área.
     */
    private function calcularResultadosPorConcepto(IntentoExamen $intento): void
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
