<?php

declare(strict_types=1);

namespace Modules\Resultados\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Modules\Examenes\Services\ExamenService;
use Modules\Rendicion\Models\IntentoExamen;
use Modules\Resultados\Mail\ResultadosMail;

class ResultadosController extends Controller
{
    public function show($id)
    {
        $intento = IntentoExamen::with([
            'institucion',
            'examen',
            'areaAcademica',
            'tipoSimulacro',
            'respuestas.pregunta.alternativas',
            'respuestas.alternativaElegida',
            'resultadosConceptos.concepto',
        ])->findOrFail($id);

        if ($intento->usuario_id !== auth()->id()) {
            abort(403);
        }

        // ─── Auto-calcular puntaje si aún no se ha calculado ───
        // Cubre el caso donde el usuario llega a resultados sin que
        // finalizar() haya completado el cálculo (ej: timeout frontend).
        if (is_null($intento->puntaje_total) && $intento->respuestas->isNotEmpty()) {
            app(ExamenService::class)->calcularPuntaje($intento);
            app(ExamenService::class)->calcularResultadosPorConcepto($intento);
            $intento->refresh();
        }

        $mensajesAyuda = \Modules\Preguntas\Models\MensajeAyuda::whereIn(
            'concepto_id',
            $intento->resultadosConceptos->pluck('concepto_id')
        )->get()->keyBy('concepto_id')->toArray();

        // Para exámenes de área académica, calcular carreras compatibles.
        // El cálculo es estable tras finalizar; se cachea por intento.
        $carrerasCompatibles = [];
        $carrerasNoCompatibles = [];

        if ($intento->area_academica_id && !$intento->examen_id) {
            $carreras = $intento->puntaje_total !== null
                ? Cache::remember("resultados.carreras.{$intento->id}", now()->addDay(), fn () => $this->calcularCarrerasCompatibles($intento))
                : $this->calcularCarrerasCompatibles($intento);

            $carrerasCompatibles = $carreras['compatibles'];
            $carrerasNoCompatibles = $carreras['no_compatibles'];
        }

        return Inertia::render('Resultados/Index', [
            'intento' => $intento,
            'institucion' => $intento->institucion,
            'respuestas' => $intento->respuestas,
            'resultadosConceptos' => $intento->resultadosConceptos,
            'mensajesAyuda' => $mensajesAyuda,
            'userEmail' => auth()->user()?->email,
            'userWhatsapp' => auth()->user()?->whatsapp_numero,
            'carrerasCompatibles' => $carrerasCompatibles,
            'carrerasNoCompatibles' => $carrerasNoCompatibles,
            'carreraAplicada' => $intento->categoria_id
                ? [
                    'id' => $intento->categoria_id,
                    'nombre' => $intento->carrera,
                ]
                : null,
        ]);
    }

    /**
     * Calcula las carreras compatibles/no compatibles para un intento de área
     * académica. Operación estable tras finalizar; se cachea por intento.
     *
     * @return array{compatibles: array, no_compatibles: array}
     */
    private function calcularCarrerasCompatibles(IntentoExamen $intento): array
    {
        $puntajePorArea = $intento->resultadosConceptos->map(fn ($rc) => [
            'concepto_id' => $rc->concepto_id,
            'nombre' => $rc->concepto?->nombre ?? 'Sin área',
            'porcentaje' => $rc->porcentaje_acierto,
        ])->values();

        $puntajeTotalEstudiante = $intento->puntaje_maximo > 0
            ? round(($intento->puntaje_total / $intento->puntaje_maximo) * 100, 2)
            : 0;

        $categoriasQuery = \Modules\Catalogo\Models\Categoria::with(['institucion', 'requisitos.concepto'])
            ->where(function ($q) {
                $q->whereHas('requisitos')
                    ->orWhere('puntaje_minimo_total', '>', 0);
            });

        // Solo comparar contra carreras de la misma universidad
        if ($intento->institucion_id) {
            $categoriasQuery->where('institucion_id', $intento->institucion_id);
        }

        $categorias = $categoriasQuery->get();

        $carrerasCompatibles = [];
        $carrerasNoCompatibles = [];

        foreach ($categorias as $categoria) {
            $requisitos = $categoria->requisitos;
            $minimoTotal = $categoria->puntaje_minimo_total ?? 0;

            $cumplePorPuntaje = $puntajeTotalEstudiante >= $minimoTotal;
            $cumpleAreas = true;
            $areasFaltantes = [];

            foreach ($requisitos as $requisito) {
                $area = $puntajePorArea->firstWhere('concepto_id', $requisito->concepto_id);
                $pctObtenido = $area['porcentaje'] ?? 0;
                $pctRequerido = $requisito->puntaje_minimo;

                if ($pctObtenido < $pctRequerido) {
                    $cumpleAreas = false;
                    $areasFaltantes[] = [
                        'nombre' => $requisito->concepto?->nombre ?? 'Área',
                        'obtenido' => $pctObtenido,
                        'requerido' => $pctRequerido,
                    ];
                }
            }

            $entry = [
                'categoria_id' => $categoria->id,
                'nombre' => $categoria->nombre,
                'institucion' => $categoria->institucion?->nombre ?? '—',
                'es_carrera_aplicada' => $intento->categoria_id === $categoria->id,
                'puntaje_obtenido' => $puntajeTotalEstudiante,
                'puntaje_minimo' => $minimoTotal,
                'cumple_puntaje' => $cumplePorPuntaje,
                'porcentaje_general' => $puntajeTotalEstudiante,
                'total_requisitos' => $requisitos->count(),
            ];

            if ($cumplePorPuntaje && ($requisitos->isEmpty() || $cumpleAreas)) {
                $carrerasCompatibles[] = $entry;
            } else {
                $entry['areas_faltantes'] = $areasFaltantes;
                $carrerasNoCompatibles[] = $entry;
            }
        }

        usort($carrerasCompatibles, fn ($a, $b) => $b['puntaje_obtenido'] <=> $a['puntaje_obtenido']);
        usort($carrerasNoCompatibles, fn ($a, $b) => $b['puntaje_obtenido'] <=> $a['puntaje_obtenido']);

        return [
            'compatibles' => $carrerasCompatibles,
            'no_compatibles' => $carrerasNoCompatibles,
        ];
    }

    /**
     * Envía los resultados por correo electrónico.
     */
    public function enviarEmail($id): JsonResponse
    {
        $intento = IntentoExamen::with([
            'institucion',
            'examen.categoria.institucion',
            'respuestas',
            'resultadosConceptos.concepto.mensajesAyuda',
        ])->findOrFail($id);

        if ($intento->usuario_id !== auth()->id()) {
            abort(403);
        }

        $user = auth()->user();
        if (!$user?->email) {
            return response()->json(['error' => 'No tienes un correo electrónico registrado.'], 400);
        }

        try {
            Mail::to($user->email)->send(new ResultadosMail($intento));

            $intento->update(['email_enviado' => true]);

            return response()->json([
                'success' => true,
                'message' => 'Resultados enviados a ' . $user->email,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'No se pudo enviar el correo. Intenta de nuevo más tarde.',
            ], 500);
        }
    }

    /**
     * Genera el link de WhatsApp para compartir resultados.
     */
    public function generarWhatsAppLink($id): JsonResponse
    {
        $intento = IntentoExamen::with([
            'institucion',
            'resultadosConceptos.concepto',
        ])->findOrFail($id);

        if ($intento->usuario_id !== auth()->id()) {
            abort(403);
        }

        $pct = $intento->puntaje_maximo > 0
            ? round(($intento->puntaje_total / $intento->puntaje_maximo) * 100)
            : 0;

        $tituloExamen = ($intento->institucion?->nombre ?? 'Simulacro') . ' - ' . ($intento->carrera ?? 'General');
        $mensaje = "📝 *Resultados - {$tituloExamen}*%0a%0a"
            . "🎯 Puntaje: {$intento->puntaje_total}/{$intento->puntaje_maximo} ({$pct}%)%0a"
            . ($intento->aprobado ? "✅ *¡Aprobado!*%0a" : "📝 *Completado*%0a")
            . "⏱️ Tiempo: " . ($intento->tiempo_empleado_seg ? floor($intento->tiempo_empleado_seg / 60) . ' min' : '—') . "%0a%0a"
            . "📊 *Desglose por tema:*%0a";

        foreach ($intento->resultadosConceptos as $rc) {
            $emoji = $rc->porcentaje_acierto >= 60 ? '✅' : ($rc->porcentaje_acierto >= 40 ? '⚠️' : '❌');
            $mensaje .= "{$emoji} {$rc->concepto?->nombre}: {$rc->porcentaje_acierto}% ({$rc->preguntas_correctas}/{$rc->preguntas_totales})%0a";
        }

        $mensaje .= "%0a🔗 *Ver resultados completos:*%0a" . url("/resultados/{$intento->id}");
        $mensaje .= "%0a%0a✉️ Enviado desde Prepárate y Postula Ya";

        $whatsappLink = "https://wa.me/?text={$mensaje}";

        $intento->update(['whatsapp_solicitado' => true]);

        return response()->json([
            'success' => true,
            'whatsapp_link' => $whatsappLink,
        ]);
    }
}
