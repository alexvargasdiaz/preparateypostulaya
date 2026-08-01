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
        if ($request->filled('nivel')) {
            $query->where('nivel', $request->nivel);
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
            ->withCount(['preguntas as preguntas_nivel_1' => fn ($q) => $q->where('nivel', 1)])
            ->withCount(['preguntas as preguntas_nivel_2' => fn ($q) => $q->where('nivel', 2)])
            ->withCount(['preguntas as preguntas_nivel_3' => fn ($q) => $q->where('nivel', 3)])
            ->orderBy('nombre')
            ->get();

        $conceptos = Concepto::orderBy('nombre')
            ->get(['id', 'nombre']);

        return Inertia::render('Admin/BaulPreguntas/Index', [
            'preguntas' => $preguntas,
            'areas' => $areas,
            'conceptos' => $conceptos,
            'filtros' => $request->only(['area_academica_id', 'nivel', 'dificultad', 'activa', 'busqueda']),
        ]);
    }

    public function actualizarArea(Request $request)
    {
        $validated = $request->validate([
            'pregunta_id' => 'required|integer|exists:preguntas,id',
            'area_academica_id' => 'nullable|integer|exists:areas_academicas,id',
            'nivel' => 'nullable|integer|in:1,2,3',
        ]);

        $pregunta = Pregunta::findOrFail($validated['pregunta_id']);
        $pregunta->update([
            'area_academica_id' => $validated['area_academica_id'] ?? null,
            'nivel' => $validated['nivel'] ?? null,
        ]);

        return response()->json(['success' => true]);
    }

    public function actualizarMasivo(Request $request)
    {
        $validated = $request->validate([
            'preguntas_ids' => 'required|array',
            'preguntas_ids.*' => 'integer|exists:preguntas,id',
            'area_academica_id' => 'nullable|integer|exists:areas_academicas,id',
            'nivel' => 'nullable|integer|in:1,2,3',
        ]);

        $data = [];
        if (isset($validated['area_academica_id'])) {
            $data['area_academica_id'] = $validated['area_academica_id'];
        }
        if (isset($validated['nivel'])) {
            $data['nivel'] = $validated['nivel'];
        }

        Pregunta::whereIn('id', $validated['preguntas_ids'])->update($data);

        return response()->json([
            'success' => true,
            'actualizadas' => count($validated['preguntas_ids']),
        ]);
    }
}
