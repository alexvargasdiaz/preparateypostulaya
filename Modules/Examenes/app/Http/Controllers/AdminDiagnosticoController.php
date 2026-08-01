<?php

declare(strict_types=1);

namespace Modules\Examenes\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Preguntas\Models\AreaAcademica;
use Modules\Preguntas\Models\DiagnosticoConcepto;

class AdminDiagnosticoController extends Controller
{
    public function index(): Response
    {
        $areas = AreaAcademica::where('activo', true)
            ->with(['conceptos' => fn ($q) => $q->orderBy('nombre')->withCount('preguntasActivas as preguntas_count')])
            ->orderBy('nombre')
            ->get(['id', 'nombre']);

        $configurados = DiagnosticoConcepto::pluck('preguntas_por_concepto', 'concepto_id');

        // Obtener duración configurada (se guarda igual en todos los registros)
        $duracionMinutos = DiagnosticoConcepto::first()?->duracion_minutos;

        return Inertia::render('Admin/Diagnostico/Configurar', [
            'areas' => $areas,
            'configurados' => $configurados,
            'duracionMinutos' => $duracionMinutos,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'conceptos' => 'array',
            'conceptos.*.id' => 'required|exists:conceptos,id',
            'conceptos.*.incluido' => 'boolean',
            'conceptos.*.preguntas_por_concepto' => 'integer|min:1|max:100',
            'duracion_minutos' => 'nullable|integer|min:1|max:300',
        ]);

        $conceptosIncluidos = collect($data['conceptos'] ?? [])->filter(fn ($c) => $c['incluido']);

        // Eliminar configuraciones de conceptos que ya no están incluidos
        $idsIncluidos = $conceptosIncluidos->pluck('id');
        DiagnosticoConcepto::whereNotIn('concepto_id', $idsIncluidos)->delete();

        // Guardar duración global (en todos los registros para mantener sincronía)
        $duracion = $data['duracion_minutos'] ?? null;

        // Actualizar o crear los incluidos
        foreach ($conceptosIncluidos as $c) {
            DiagnosticoConcepto::updateOrCreate(
                ['concepto_id' => $c['id']],
                [
                    'preguntas_por_concepto' => $c['preguntas_por_concepto'] ?? 10,
                    'duracion_minutos' => $duracion,
                ]
            );
        }

        return back()->with('success', 'Configuración del diagnóstico guardada.');
    }
}
