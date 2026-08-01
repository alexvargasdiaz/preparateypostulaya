<?php

declare(strict_types=1);

namespace Modules\Catalogo\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Catalogo\Models\Institucion;

class InstitucionController extends Controller
{
    public function index(): JsonResponse
    {
        $instituciones = Institucion::with('tipoExamen')
            ->withCount('categorias')
            ->orderBy('nombre')
            ->get();
        return response()->json($instituciones);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'tipo_examen_id' => 'required|exists:tipos_examen,id',
            'nombre' => 'required|string|max:200',
            'subtipo' => 'nullable|string|max:60',
            'ciudad' => 'nullable|string|max:100',
            'logo_url' => 'nullable|url|max:500',
            'activo' => 'boolean',
        ]);

        $institucion = Institucion::create($validated);
        return response()->json($institucion, 201);
    }

    public function show($id): JsonResponse
    {
        $institucion = Institucion::with(['tipoExamen', 'categorias' => function ($q) {
            $q->where('activo', true)->orderBy('orden');
        }])->findOrFail($id);
        return response()->json($institucion);
    }

    public function update(Request $request, $id): JsonResponse
    {
        $institucion = Institucion::findOrFail($id);
        $validated = $request->validate([
            'tipo_examen_id' => 'required|exists:tipos_examen,id',
            'nombre' => 'required|string|max:200',
            'subtipo' => 'nullable|string|max:60',
            'ciudad' => 'nullable|string|max:100',
            'logo_url' => 'nullable|url|max:500',
            'activo' => 'boolean',
        ]);

        $institucion->update($validated);
        return response()->json($institucion);
    }

    public function destroy($id): JsonResponse
    {
        $institucion = Institucion::findOrFail($id);
        if ($institucion->categorias()->exists()) {
            return response()->json([
                'message' => 'No se puede eliminar una institución con categorías asociadas.'
            ], 409);
        }
        $institucion->delete();
        return response()->json(null, 204);
    }
}
