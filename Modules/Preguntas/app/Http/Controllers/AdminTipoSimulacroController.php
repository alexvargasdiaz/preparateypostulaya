<?php

declare(strict_types=1);

namespace Modules\Preguntas\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Preguntas\Models\AreaAcademica;
use Modules\Preguntas\Models\TipoSimulacro;

class AdminTipoSimulacroController extends Controller
{
    public function index(Request $request): Response
    {
        $area = AreaAcademica::withCount('preguntas')->findOrFail($request->query('area_id'));

        $tipos = TipoSimulacro::where('area_academica_id', $area->id)
            ->orderBy('nombre')
            ->get();

        return Inertia::render('Admin/TiposSimulacro/Index', [
            'area' => $area,
            'tipos' => $tipos,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'area_academica_id' => 'required|integer|exists:areas_academicas,id',
            'nombre' => ['required', 'string', 'max:200'],
            'descripcion' => ['nullable', 'string'],
            'num_preguntas' => ['required', 'integer', 'min:10', 'max:200'],
            'duracion_min' => ['required', 'integer', 'min:30', 'max:300'],
        ]);

        TipoSimulacro::create($validated);

        return back()->with('success', 'Tipo de simulacro creado correctamente.');
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $tipo = TipoSimulacro::findOrFail($id);

        $validated = $request->validate([
            'nombre' => ['required', 'string', 'max:200'],
            'descripcion' => ['nullable', 'string'],
            'num_preguntas' => ['required', 'integer', 'min:10', 'max:200'],
            'duracion_min' => ['required', 'integer', 'min:30', 'max:300'],
            'activo' => ['boolean'],
        ]);

        $tipo->update($validated);

        return back()->with('success', 'Tipo de simulacro actualizado.');
    }

    public function destroy(int $id): RedirectResponse
    {
        $tipo = TipoSimulacro::findOrFail($id);

        if ($tipo->intentos()->count() > 0) {
            return back()->with('error', 'No se puede eliminar: este tipo tiene intentos registrados.');
        }

        $tipo->delete();

        return back()->with('success', 'Tipo de simulacro eliminado.');
    }
}
