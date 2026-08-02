<?php

declare(strict_types=1);

namespace Modules\Preguntas\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Inertia\Inertia;
use Modules\Preguntas\Models\AreaAcademica;
use Modules\Preguntas\Models\Concepto;
use Modules\Preguntas\Models\Pregunta;

class AdminBaulPreguntasController extends Controller
{
    public function index(Request $request)
    {
        $query = Pregunta::with([
            'areaAcademica',
            'concepto',
        ]);

        if ($request->filled('area_academica_id')) {
            $query->where('area_academica_id', $request->area_academica_id);
        }
        if ($request->filled('dificultad')) {
            $query->where('dificultad', $request->dificultad);
        }
        if ($request->filled('activa')) {
            $query->where('activa', $request->boolean('activa'));
        }
        if ($request->filled('busqueda')) {
            $query->where('enunciado', 'like', "%{$request->busqueda}%");
        }

        $preguntas = $query->orderBy('created_at', 'desc')->paginate(20)->withQueryString();

        $areas = AreaAcademica::where('activo', true)
            ->withCount(['preguntas as total_preguntas'])
            ->withCount(['preguntas as preguntas_facil' => fn ($q) => $q->where('dificultad', 'facil')])
            ->withCount(['preguntas as preguntas_media' => fn ($q) => $q->where('dificultad', 'media')])
            ->withCount(['preguntas as preguntas_dificil' => fn ($q) => $q->where('dificultad', 'dificil')])
            ->orderBy('nombre')
            ->get();

        $conceptos = Concepto::orderBy('nombre')
            ->get(['id', 'nombre']);

        return Inertia::render('Admin/BaulPreguntas/Index', [
            'preguntas' => $preguntas,
            'areas' => $areas,
            'conceptos' => $conceptos,
            'filtros' => $request->only(['area_academica_id', 'dificultad', 'activa', 'busqueda']),
        ]);
    }

    public function actualizarArea(Request $request)
    {
        $validated = $request->validate([
            'pregunta_id' => 'required|integer|exists:preguntas,id',
            'area_academica_id' => 'nullable|integer|exists:areas_academicas,id',
        ]);

        $pregunta = Pregunta::findOrFail($validated['pregunta_id']);
        $pregunta->update([
            'area_academica_id' => $validated['area_academica_id'] ?? null,
        ]);

        return response()->json(['success' => true]);
    }

    public function actualizarMasivo(Request $request)
    {
        $validated = $request->validate([
            'preguntas_ids' => 'required|array',
            'preguntas_ids.*' => 'integer|exists:preguntas,id',
            'area_academica_id' => 'nullable|integer|exists:areas_academicas,id',
        ]);

        $data = [];
        if (isset($validated['area_academica_id'])) {
            $data['area_academica_id'] = $validated['area_academica_id'];
        }

        Pregunta::whereIn('id', $validated['preguntas_ids'])->update($data);

        return response()->json([
            'success' => true,
            'actualizadas' => count($validated['preguntas_ids']),
        ]);
    }
}
