<?php

declare(strict_types=1);

namespace Modules\Catalogo\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Str;
use Modules\Catalogo\Models\TipoExamen;

class TipoExamenController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $tipos = TipoExamen::withCount('instituciones')
            ->orderBy('nombre')
            ->paginate($request->integer('per_page', 50));
        return response()->json($tipos);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:120|unique:tipo_examen,nombre',
            'descripcion' => 'nullable|string',
            'activo' => 'boolean',
        ]);

        $validated['slug'] = Str::slug($validated['nombre']);
        $tipo = TipoExamen::create($validated);

        return response()->json($tipo, 201);
    }

    public function show(TipoExamen $tipoExamen): JsonResponse
    {
        $tipoExamen->load(['instituciones' => function ($q) {
            $q->where('activo', true)->orderBy('nombre');
        }]);
        return response()->json($tipoExamen);
    }

    public function update(Request $request, TipoExamen $tipoExamen): JsonResponse
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:120|unique:tipo_examen,nombre,' . $tipoExamen->id,
            'descripcion' => 'nullable|string',
            'activo' => 'boolean',
        ]);

        $validated['slug'] = Str::slug($validated['nombre']);
        $tipoExamen->update($validated);

        return response()->json($tipoExamen);
    }

    public function destroy(TipoExamen $tipoExamen): JsonResponse
    {
        if ($tipoExamen->instituciones()->exists()) {
            return response()->json([
                'message' => 'No se puede eliminar un tipo de examen con instituciones asociadas.'
            ], 409);
        }
        $tipoExamen->delete();
        return response()->json(null, 204);
    }
}
