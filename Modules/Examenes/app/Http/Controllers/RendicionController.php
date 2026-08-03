<?php

declare(strict_types=1);

namespace Modules\Examenes\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Modules\Catalogo\Models\Examen;
use Modules\Examenes\Services\ExamenService;
use Modules\Preguntas\Models\Alternativa;
use Modules\Preguntas\Models\Pregunta;
use Modules\Rendicion\Models\IntentoExamen;
use Modules\Rendicion\Models\RespuestaUsuario;
use Modules\Notificaciones\Services\NotificationService;

class RendicionController extends Controller
{
    public function __construct(
        private readonly ExamenService $examenService,
    ) {}

    /**
     * Inicia un nuevo intento para un examen específico.
     */
    public function iniciar(Request $request)
    {
        $examenId = $request->query('examen_id');

        if (!$examenId) {
            return redirect()->route('examenes.index')->with('error', 'Debes seleccionar un examen.');
        }

        $examen = Examen::findOrFail($examenId);

        if (!$examen->activo) {
            return redirect()->route('examenes.index')->with('error', 'Este examen no está disponible.');
        }

        $totalCount = $examen->preguntasDelExamen()->where('activa', true)->count();

        if ($totalCount === 0 && $examen->area_academica_id) {
            $totalCount = Pregunta::where('area_academica_id', $examen->area_academica_id)
                ->where('activa', true)
                ->count();
        }

        if ($totalCount === 0) {
            return redirect()->route('examenes.index')->with('error', 'Este examen aún no tiene preguntas.');
        }

        $intento = $this->examenService->iniciarIntento($examen);

        return redirect()->route('examenes.rendir', ['intento' => $intento->id]);
    }

    /**
     * Muestra la pantalla de rendición del simulacro.
     */
    public function rendir($intentoId)
    {
        $intento = IntentoExamen::with(['examen', 'areaAcademica', 'tipoSimulacro'])
            ->findOrFail($intentoId);

        if ($intento->usuario_id !== auth()->id()) {
            abort(403);
        }

        // Obtener las preguntas seleccionadas para este intento (desde progreso_guardado)
        $preguntasIds = $intento->progreso_guardado['preguntas_ids'] ?? [];

        if (empty($preguntasIds)) {
            // Fallback: si no hay preguntas guardadas, tomar todas las del área
            $preguntas = Pregunta::with(['alternativas' => fn ($q) => $q->inRandomOrder()])
                ->where('area_academica_id', $intento->area_academica_id)
                ->where('activa', true)
                ->inRandomOrder()
                ->limit($intento->puntaje_maximo ?? 30)
                ->get();
        } else {
            $preguntas = Pregunta::with(['alternativas' => fn ($q) => $q->inRandomOrder()])
                ->whereIn('id', $preguntasIds)
                ->where('activa', true)
                ->inRandomOrder()
                ->get();
        }

        // Respuestas ya guardadas
        $respuestasGuardadas = RespuestaUsuario::where('intento_id', $intento->id)
            ->get()
            ->keyBy('pregunta_id');

        // Enmascarar la alternativa correcta
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

        $institucion = $intento->institucion ?? $intento->areaAcademica;

        $tiempoLimite = $intento->tipoSimulacro?->duracion_min
            ?? $intento->examen?->tiempo_limite_min
            ?? $intento->areaAcademica?->duracion_min
            ?? 20;

        return Inertia::render('Rendicion/Index', [
            'intento' => $intento,
            'institucion' => $institucion?->only(['id', 'nombre', 'subtipo']),
            'preguntas' => $preguntasParaFrontend,
            'tiempoRestante' => $tiempoLimite * 60,
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
     * Finaliza el simulacro y calcula resultados.
     */
    public function finalizar($intentoId)
    {
        $intento = IntentoExamen::with(['examen', 'respuestas'])
            ->findOrFail($intentoId);

        if ($intento->usuario_id !== auth()->id()) {
            abort(403);
        }

        if ($intento->estado === 'completado') {
            return redirect()->route('resultados.show', $intento->id);
        }

        $intento->update([
            'estado' => 'completado',
            'fecha_fin' => now(),
            'tiempo_empleado_seg' => $intento->fecha_inicio->diffInSeconds(now()),
        ]);

        $this->examenService->calcularPuntaje($intento);
        $this->examenService->calcularResultadosPorConcepto($intento);

        try {
            $tituloExamen = $intento->examen?->titulo
                ?? $intento->areaAcademica?->nombre
                ?? 'Simulacro';

            $notificationService = app(NotificationService::class);
            $notificationService->examenCompletado(
                usuario: $intento->usuario_id,
                examenTitulo: $tituloExamen,
                puntaje: $intento->puntaje_total,
                maximo: $intento->puntaje_maximo,
                aprobado: $intento->aprobado,
                intentoId: $intento->id,
            );
        } catch (\Exception $e) {
            report($e);
        }

        return redirect()->route('resultados.show', $intento->id);
    }
}
