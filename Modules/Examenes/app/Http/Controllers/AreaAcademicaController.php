<?php

declare(strict_types=1);

namespace Modules\Examenes\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Catalogo\Models\Examen;
use Modules\Preguntas\Models\AreaAcademica;
use Modules\Preguntas\Models\Pregunta;
use Modules\Preguntas\Models\TipoSimulacro;
use Modules\Rendicion\Models\IntentoExamen;

class AreaAcademicaController extends Controller
{
    public function index(): Response
    {
        $user = auth()->user();

        $areas = AreaAcademica::where('activo', true)
            ->withCount(['preguntas as total_preguntas' => fn ($q) => $q->where('activa', true)])
            ->orderBy('nombre')
            ->get();

        $intentosPorArea = IntentoExamen::where('usuario_id', $user->id)
            ->whereNotNull('area_academica_id')
            ->where('estado', 'completado')
            ->selectRaw('area_academica_id, COUNT(*) as total, SUM(CASE WHEN aprobado THEN 1 ELSE 0 END) as aprobados, ROUND(AVG(CASE WHEN puntaje_maximo > 0 THEN (puntaje_total / puntaje_maximo) * 100 ELSE 0 END)) as promedio')
            ->groupBy('area_academica_id')
            ->pluck('total', 'area_academica_id')
            ->toArray();

        return Inertia::render('Examenes/AreasAcademicas/Index', [
            'areas' => $areas,
            'intentosPorArea' => $intentosPorArea,
        ]);
    }

    public function tipos(int $areaId): Response
    {
        $user = auth()->user();

        $area = AreaAcademica::where('activo', true)
            ->withCount(['preguntas as total_preguntas' => fn ($q) => $q->where('activa', true)])
            ->findOrFail($areaId);

        // Mostrar exámenes del área (en lugar de solo tipos de simulacro)
        $examenes = Examen::where('area_academica_id', $areaId)
            ->where('activo', true)
            ->with('conceptos')
            ->orderBy('titulo')
            ->get()
            ->map(function ($ex) use ($user) {
                $totalPreguntas = 0;
                foreach ($ex->conceptos as $concepto) {
                    $count = Pregunta::where('concepto_id', $concepto->id)
                        ->where('activa', true)
                        ->count();
                    $totalPreguntas += min($count, $concepto->pivot->num_preguntas);
                }
                return [
                    'id' => $ex->id,
                    'titulo' => $ex->titulo,
                    'descripcion' => $ex->descripcion,
                    'tiempo_limite_min' => $ex->tiempo_limite_min,
                    'preguntas_totales' => $totalPreguntas,
                    'intentos_permitidos' => $ex->intentos_permitidos,
                ];
            });

        // También mantener tipos de simulacro para compatibilidad
        $tipos = TipoSimulacro::where('area_academica_id', $areaId)
            ->where('activo', true)
            ->withCount(['intentos as total_intentos' => fn ($q) => $q->where('usuario_id', $user->id)])
            ->orderBy('nombre')
            ->get()
            ->map(fn ($t) => [
                'id' => $t->id,
                'nombre' => $t->nombre,
                'descripcion' => $t->descripcion,
                'num_preguntas' => $t->num_preguntas,
                'duracion_min' => $t->duracion_min,
                'total_intentos' => $t->total_intentos,
            ]);

        $intentoActivo = IntentoExamen::where('usuario_id', $user->id)
            ->where('area_academica_id', $areaId)
            ->where('estado', 'en_curso')
            ->first();

        return Inertia::render('Examenes/AreasAcademicas/Tipos', [
            'area' => $area,
            'examenes' => $examenes,
            'tipos' => $tipos,
            'intentoActivo' => $intentoActivo,
        ]);
    }

    public function iniciar(Request $request, int $areaId, int $tipoId): RedirectResponse
    {
        $user = auth()->user();

        $area = AreaAcademica::where('activo', true)->findOrFail($areaId);
        $tipo = TipoSimulacro::where('area_academica_id', $areaId)
            ->where('activo', true)
            ->findOrFail($tipoId);

        $intentoActivo = IntentoExamen::where('usuario_id', $user->id)
            ->where('area_academica_id', $areaId)
            ->where('estado', 'en_curso')
            ->first();

        if ($intentoActivo) {
            return redirect()->route('examenes.rendir', ['intento' => $intentoActivo->id]);
        }

        // Seleccionar preguntas por concepto
        $numPreguntas = $tipo->num_preguntas;

        // Obtener todos los conceptos del área con conteo de preguntas activas
        $conceptos = \Modules\Preguntas\Models\Concepto::where('area_academica_id', $areaId)
            ->withCount(['preguntasActivas'])
            ->get();

        $totalDisponibles = $conceptos->sum('preguntas_activas_count');

        if ($totalDisponibles === 0) {
            return back()->with('error', "El área \"{$area->nombre}\" no tiene preguntas disponibles.");
        }

        // Distribuir preguntas proporcionalmente entre conceptos
        $preguntasIds = [];
        $limite = min($numPreguntas, $totalDisponibles);
        $asignadas = 0;

        foreach ($conceptos as $concepto) {
            if ($asignadas >= $limite) break;

            $proporcion = $concepto->preguntas_activas_count / $totalDisponibles;
            $paraConcepto = max(1, (int) round($proporcion * $limite));
            $paraConcepto = min($paraConcepto, $concepto->preguntas_activas_count, $limite - $asignadas);

            if ($paraConcepto <= 0) continue;

            $ids = Pregunta::where('concepto_id', $concepto->id)
                ->where('activa', true)
                ->inRandomOrder()
                ->limit($paraConcepto)
                ->pluck('id')
                ->toArray();

            $preguntasIds = array_merge($preguntasIds, $ids);
            $asignadas += count($ids);
        }

        // Si faltan preguntas (por redondeo), completar con preguntas aleatorias del área
        if ($asignadas < $limite) {
            $faltantes = $limite - $asignadas;
            $extraIds = Pregunta::where('area_academica_id', $areaId)
                ->where('activa', true)
                ->whereNotIn('id', $preguntasIds)
                ->inRandomOrder()
                ->limit($faltantes)
                ->pluck('id')
                ->toArray();
            $preguntasIds = array_merge($preguntasIds, $extraIds);
        }

        // Mezclar preguntas para que no aparezcan agrupadas por concepto
        shuffle($preguntasIds);

        $intento = IntentoExamen::create([
            'usuario_id' => $user->id,
            'area_academica_id' => $area->id,
            'tipo_simulacro_id' => $tipo->id,
            'carrera' => $area->nombre,
            'estado' => 'en_curso',
            'puntaje_maximo' => count($preguntasIds),
            'puntaje_total' => 0,
            'fecha_inicio' => now(),
            'progreso_guardado' => [
                'preguntas_ids' => $preguntasIds,
                'tipo_simulacro' => $tipo->nombre,
            ],
        ]);

        return redirect()->route('examenes.rendir', ['intento' => $intento->id]);
    }

    /**
     * Inicia un examen directo (con configuración por concepto).
     */
    public function iniciarExamen(Request $request, int $areaId, int $examenId): RedirectResponse
    {
        $user = auth()->user();

        $area = AreaAcademica::where('activo', true)->findOrFail($areaId);
        $examen = Examen::where('area_academica_id', $areaId)
            ->where('activo', true)
            ->findOrFail($examenId);

        $intentoActivo = IntentoExamen::where('usuario_id', $user->id)
            ->where('area_academica_id', $areaId)
            ->where('estado', 'en_curso')
            ->first();

        if ($intentoActivo) {
            return redirect()->route('examenes.rendir', ['intento' => $intentoActivo->id]);
        }

        // Usar ExamenService para seleccionar preguntas por concepto
        $service = app(\Modules\Examenes\Services\ExamenService::class);
        $intento = $service->iniciarIntento($examen);

        return redirect()->route('examenes.rendir', ['intento' => $intento->id]);
    }
}
