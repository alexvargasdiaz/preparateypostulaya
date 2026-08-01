<?php

declare(strict_types=1);

namespace Modules\Preguntas\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Preguntas\Models\AreaAcademica;

class AdminAreaAcademicaController extends Controller
{
    public function index(): Response
    {
        $areas = AreaAcademica::withCount('preguntas')
            ->orderBy('nombre')
            ->get();

        return Inertia::render('Admin/AreasAcademicas/Index', [
            'areas' => $areas,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'nombre' => ['required', 'string', 'max:200'],
            'descripcion' => ['nullable', 'string'],
            'num_preguntas' => ['required', 'integer', 'min:10', 'max:200'],
            'duracion_min' => ['required', 'integer', 'min:30', 'max:300'],
        ]);

        AreaAcademica::create($validated);

        return back()->with('success', 'Área académica creada correctamente.');
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $area = AreaAcademica::findOrFail($id);

        $validated = $request->validate([
            'nombre' => ['required', 'string', 'max:200'],
            'descripcion' => ['nullable', 'string'],
            'num_preguntas' => ['required', 'integer', 'min:10', 'max:200'],
            'duracion_min' => ['required', 'integer', 'min:30', 'max:300'],
            'activo' => ['boolean'],
        ]);

        $area->update($validated);

        return back()->with('success', 'Área académica actualizada.');
    }

    public function destroy(int $id): RedirectResponse
    {
        $area = AreaAcademica::findOrFail($id);

        if ($area->preguntas()->count() > 0) {
            return back()->with('error', 'No se puede eliminar: el área tiene preguntas asociadas.');
        }

        $area->delete();

        return back()->with('success', 'Área académica eliminada.');
    }
}
