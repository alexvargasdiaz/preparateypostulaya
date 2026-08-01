<?php

declare(strict_types=1);

namespace Modules\Catalogo\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Inertia\Inertia;
use Modules\Catalogo\Models\Categoria;
use Modules\Catalogo\Models\Institucion;
use Modules\Preguntas\Models\AreaAcademica;

class AdminCategoriaController extends Controller
{
    public function index(Request $request)
    {
        $query = Categoria::with('institucion:id,nombre')
            ->withCount('examenes');

        if ($request->filled('institucion_id')) {
            $query->where('institucion_id', $request->institucion_id);
        }
        if ($request->filled('busqueda')) {
            $q = $request->busqueda;
            $query->where('nombre', 'like', "%{$q}%");
        }

        $categorias = $query->orderBy('institucion_id')->orderBy('nombre')->paginate(15);
        $instituciones = Institucion::where('activo', true)->orderBy('nombre')->get(['id', 'nombre']);

        return Inertia::render('Admin/Categorias/Index', [
            'categorias' => $categorias,
            'instituciones' => $instituciones,
            'filtros' => $request->only(['institucion_id', 'busqueda']),
        ]);
    }

    public function create()
    {
        $instituciones = Institucion::where('activo', true)->orderBy('nombre')->get(['id', 'nombre']);
        $areas = AreaAcademica::where('activo', true)->orderBy('nombre')->get(['id', 'nombre']);
        return Inertia::render('Admin/Categorias/Crear', [
            'instituciones' => $instituciones,
            'areas' => $areas,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'institucion_id' => 'required|exists:instituciones,id',
            'area_academica_id' => 'nullable|exists:areas_academicas,id',
            'nombre' => 'required|string|max:200',
            'descripcion_corta' => 'nullable|string|max:500',
        ]);

        Categoria::create([
            'institucion_id' => $validated['institucion_id'],
            'area_academica_id' => $validated['area_academica_id'] ?? null,
            'nombre' => $validated['nombre'],
            'descripcion_corta' => $validated['descripcion_corta'] ?? '',
            'activo' => true,
        ]);

        return redirect()->route('admin.categorias.index')
            ->with('success', "Carrera '{$validated['nombre']}' creada correctamente.");
    }

    public function edit($id)
    {
        $categoria = Categoria::with('institucion:id,nombre', 'areaAcademica:id,nombre')->findOrFail($id);
        $instituciones = Institucion::where('activo', true)->orderBy('nombre')->get(['id', 'nombre']);
        $areas = AreaAcademica::where('activo', true)->orderBy('nombre')->get(['id', 'nombre']);

        return Inertia::render('Admin/Categorias/Crear', [
            'categoria' => $categoria,
            'instituciones' => $instituciones,
            'areas' => $areas,
            'editando' => true,
        ]);
    }

    public function update(Request $request, $id): RedirectResponse
    {
        $categoria = Categoria::findOrFail($id);

        $validated = $request->validate([
            'institucion_id' => 'required|exists:instituciones,id',
            'area_academica_id' => 'nullable|exists:areas_academicas,id',
            'nombre' => 'required|string|max:200',
            'descripcion_corta' => 'nullable|string|max:500',
        ]);

        $categoria->update($validated);

        return redirect()->route('admin.categorias.index')
            ->with('success', "Carrera '{$validated['nombre']}' actualizada.");
    }

    public function destroy($id): RedirectResponse
    {
        $categoria = Categoria::findOrFail($id);

        if ($categoria->examenes()->exists()) {
            return back()->with('error', 'No se puede eliminar una carrera con exámenes asociados.');
        }

        $nombre = $categoria->nombre;
        $categoria->delete();

        return redirect()->route('admin.categorias.index')
            ->with('success', "Carrera '{$nombre}' eliminada.");
    }
}
