<?php

declare(strict_types=1);

namespace Modules\Examenes\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Catalogo\Models\Categoria;
use Modules\Preguntas\Models\Concepto;
use Modules\Catalogo\Models\Institucion;
use Modules\Catalogo\Models\RequisitosCarrera;

class AdminRequisitosCarreraController extends Controller
{
    /**
     * Lista las carreras con sus requisitos por área.
     */
    public function index(Request $request): Response
    {
        $query = Categoria::with(['institucion', 'requisitos.concepto'])
            ->where('activo', true);

        if ($request->filled('institucion_id')) {
            $query->where('institucion_id', $request->institucion_id);
        }

        if ($request->filled('busqueda')) {
            $q = $request->busqueda;
            $query->where('nombre', 'like', "%{$q}%");
        }

        $categorias = $query->orderBy('nombre')->get();
        $instituciones = Institucion::where('activo', true)->orderBy('nombre')->get(['id', 'nombre']);
        $conceptos = Concepto::orderBy('nombre')->get(['id', 'nombre']);

        return Inertia::render('Admin/RequisitosCarrera/Index', [
            'categorias' => $categorias,
            'instituciones' => $instituciones,
            'conceptos' => $conceptos,
            'filtros' => $request->only(['institucion_id', 'busqueda']),
        ]);
    }

    /**
     * Guarda el puntaje mínimo total de una carrera.
     */
    public function guardarPuntajeMinimo(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'categoria_id' => ['required', 'exists:categorias,id'],
            'puntaje_minimo_total' => ['required', 'numeric', 'min:0', 'max:100'],
        ]);

        Categoria::where('id', $validated['categoria_id'])
            ->update(['puntaje_minimo_total' => $validated['puntaje_minimo_total']]);

        return back()->with('success', 'Puntaje mínimo actualizado.');
    }

    /**
     * Guarda los requisitos por área para una carrera específica.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'categoria_id' => ['required', 'exists:categorias,id'],
            'requisitos' => ['required', 'array', 'min:1'],
            'requisitos.*.concepto_id' => ['required', 'exists:conceptos,id'],
            'requisitos.*.puntaje_minimo' => ['required', 'numeric', 'min:0', 'max:100'],
        ]);

        $categoriaId = $validated['categoria_id'];

        foreach ($validated['requisitos'] as $req) {
            RequisitosCarrera::updateOrCreate(
                ['categoria_id' => $categoriaId, 'concepto_id' => $req['concepto_id']],
                ['puntaje_minimo' => $req['puntaje_minimo']]
            );
        }

        return back()->with('success', 'Requisitos por área actualizados correctamente.');
    }

    /**
     * Elimina un requisito específico.
     */
    public function destroy(int $id): RedirectResponse
    {
        $requisito = RequisitosCarrera::findOrFail($id);
        $requisito->delete();

        return back()->with('success', 'Requisito eliminado.');
    }

    /**
     * Elimina todos los requisitos de una carrera.
     */
    public function destroyAll(int $categoriaId): RedirectResponse
    {
        RequisitosCarrera::where('categoria_id', $categoriaId)->delete();

        return back()->with('success', 'Todos los requisitos de la carrera fueron eliminados.');
    }
}
