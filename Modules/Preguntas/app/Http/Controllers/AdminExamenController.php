<?php

declare(strict_types=1);

namespace Modules\Preguntas\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Inertia\Inertia;
use Modules\Catalogo\Models\Categoria;
use Modules\Catalogo\Models\Examen;
use Modules\Catalogo\Models\Institucion;
use Modules\Preguntas\Models\AreaAcademica;
use Modules\Preguntas\Models\Concepto;

class AdminExamenController extends Controller
{
    public function index(Request $request)
    {
        $query = Examen::with('areaAcademica', 'categoria.institucion')
            ->withCount(['conceptos as conceptos_count']);

        if ($request->filled('area_academica_id')) {
            $query->where('area_academica_id', $request->area_academica_id);
        }
        if ($request->filled('busqueda')) {
            $q = $request->busqueda;
            $query->where('titulo', 'like', "%{$q}%");
        }

        $examenes = $query->orderBy('created_at', 'desc')->paginate(15);
        $areas = AreaAcademica::where('activo', true)->orderBy('nombre')->get(['id', 'nombre']);

        return Inertia::render('Admin/Examenes/Index', [
            'examenes' => $examenes,
            'areas' => $areas,
            'filtros' => $request->only(['area_academica_id', 'busqueda']),
        ]);
    }

    public function create()
    {
        $areas = AreaAcademica::where('activo', true)
            ->orderBy('nombre')
            ->get(['id', 'nombre']);

        $conceptos = Concepto::with('areaAcademica')
            ->orderBy('nombre')
            ->get(['id', 'nombre', 'area_academica_id']);

        $instituciones = Institucion::where('activo', true)
            ->orderBy('nombre')
            ->get(['id', 'nombre']);

        $categorias = Categoria::where('activo', true)
            ->with('institucion')
            ->orderBy('nombre')
            ->get(['id', 'nombre', 'institucion_id', 'area_academica_id']);

        return Inertia::render('Admin/Examenes/Crear', [
            'areas' => $areas,
            'conceptos' => $conceptos,
            'instituciones' => $instituciones,
            'categorias' => $categorias,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'area_academica_id' => 'required|exists:areas_academicas,id',
            'categoria_id' => 'nullable|exists:categorias,id',
            'titulo' => 'required|string|max:255',
            'descripcion' => 'nullable|string|max:1000',
            'tiempo_limite_min' => 'nullable|integer|min:1|max:180',
            'intentos_permitidos' => 'nullable|integer|min:1|max:99',
            'conceptos' => 'required|array|min:1',
            'conceptos.*.id' => 'required|exists:conceptos,id',
            'conceptos.*.num_preguntas' => 'required|integer|min:1|max:200',
        ]);

        $examen = Examen::create([
            'area_academica_id' => $validated['area_academica_id'],
            'categoria_id' => $validated['categoria_id'] ?? null,
            'titulo' => $validated['titulo'],
            'descripcion' => $validated['descripcion'] ?? '',
            'tiempo_limite_min' => $validated['tiempo_limite_min'] ?? 20,
            'intentos_permitidos' => $validated['intentos_permitidos'] ?? 99,
            'preguntas_por_intento' => collect($validated['conceptos'])->sum('num_preguntas'),
            'num_alternativas_default' => 5,
            'aleatorizar_preguntas' => true,
            'aleatorizar_alternativas' => true,
            'activo' => true,
        ]);

        $conceptosData = [];
        foreach ($validated['conceptos'] as $c) {
            $conceptosData[$c['id']] = ['num_preguntas' => $c['num_preguntas']];
        }
        $examen->conceptos()->sync($conceptosData);

        return redirect()->route('admin.examenes.index')
            ->with('success', 'Examen creado correctamente.');
    }

    public function edit($id)
    {
        $examen = Examen::with('conceptos', 'categoria.institucion')->findOrFail($id);

        $areas = AreaAcademica::where('activo', true)
            ->orderBy('nombre')
            ->get(['id', 'nombre']);

        $conceptos = Concepto::with('areaAcademica')
            ->orderBy('nombre')
            ->get(['id', 'nombre', 'area_academica_id']);

        $instituciones = Institucion::where('activo', true)
            ->orderBy('nombre')
            ->get(['id', 'nombre']);

        $categorias = Categoria::where('activo', true)
            ->with('institucion')
            ->orderBy('nombre')
            ->get(['id', 'nombre', 'institucion_id', 'area_academica_id']);

        return Inertia::render('Admin/Examenes/Crear', [
            'examen' => $examen,
            'areas' => $areas,
            'conceptos' => $conceptos,
            'instituciones' => $instituciones,
            'categorias' => $categorias,
            'editando' => true,
        ]);
    }

    public function update(Request $request, $id): RedirectResponse
    {
        $examen = Examen::findOrFail($id);

        $validated = $request->validate([
            'area_academica_id' => 'required|exists:areas_academicas,id',
            'categoria_id' => 'nullable|exists:categorias,id',
            'titulo' => 'required|string|max:255',
            'descripcion' => 'nullable|string|max:1000',
            'tiempo_limite_min' => 'nullable|integer|min:1|max:180',
            'intentos_permitidos' => 'nullable|integer|min:1|max:99',
            'conceptos' => 'required|array|min:1',
            'conceptos.*.id' => 'required|exists:conceptos,id',
            'conceptos.*.num_preguntas' => 'required|integer|min:1|max:200',
        ]);

        $examen->update([
            'area_academica_id' => $validated['area_academica_id'],
            'categoria_id' => $validated['categoria_id'] ?? null,
            'titulo' => $validated['titulo'],
            'descripcion' => $validated['descripcion'] ?? '',
            'tiempo_limite_min' => $validated['tiempo_limite_min'] ?? 20,
            'intentos_permitidos' => $validated['intentos_permitidos'] ?? 99,
            'preguntas_por_intento' => collect($validated['conceptos'])->sum('num_preguntas'),
        ]);

        $conceptosData = [];
        foreach ($validated['conceptos'] as $c) {
            $conceptosData[$c['id']] = ['num_preguntas' => $c['num_preguntas']];
        }
        $examen->conceptos()->sync($conceptosData);

        return redirect()->route('admin.examenes.index')
            ->with('success', 'Examen actualizado correctamente.');
    }

    public function destroy($id): RedirectResponse
    {
        $examen = Examen::findOrFail($id);

        $totalPreguntas = Concepto::whereHas('examenes', fn ($q) => $q->where('examen_id', $examen->id))
            ->withCount('preguntasActivas')
            ->get()
            ->sum('preguntas_activas_count');

        if ($totalPreguntas > 0) {
            return back()->with('error', "No se puede eliminar: hay {$totalPreguntas} preguntas en los conceptos asociados. Elimina las preguntas primero.");
        }

        $examen->conceptos()->detach();
        $examen->delete();

        return redirect()->route('admin.examenes.index')
            ->with('success', 'Examen eliminado.');
    }
}
