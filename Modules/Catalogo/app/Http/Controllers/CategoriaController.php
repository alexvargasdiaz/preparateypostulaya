<?php

declare(strict_types=1);

namespace Modules\Catalogo\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Catalogo\Models\Categoria;

class CategoriaController extends Controller
{
    public function index(): JsonResponse
    {
        $categorias = Categoria::with('institucion')
            ->withCount('examenes')
            ->orderBy('institucion_id')
            ->orderBy('orden')
            ->get();
        return response()->json($categorias);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'institucion_id' => 'required|exists:instituciones,id',
            'nombre' => 'required|string|max:200',
            'descripcion_corta' => 'nullable|string',
            'imagen_url' => 'nullable|url|max:500',
            'orden' => 'integer|min:0',
            'activo' => 'boolean',
        ]);

        $categoria = Categoria::create($validated);
        return response()->json($categoria, 201);
    }

    public function show($id): JsonResponse
    {
        $categoria = Categoria::with(['institucion', 'examenes' => function ($q) {
            $q->where('activo', true);
        }])->findOrFail($id);
        return response()->json($categoria);
    }

    public function update(Request $request, $id): JsonResponse
    {
        $categoria = Categoria::findOrFail($id);
        $validated = $request->validate([
            'institucion_id' => 'required|exists:instituciones,id',
            'nombre' => 'required|string|max:200',
            'descripcion_corta' => 'nullable|string',
            'imagen_url' => 'nullable|url|max:500',
            'orden' => 'integer|min:0',
            'activo' => 'boolean',
        ]);

        $categoria->update($validated);
        return response()->json($categoria);
    }

    public function destroy($id): JsonResponse
    {
        $categoria = Categoria::findOrFail($id);
        if ($categoria->examenes()->exists()) {
            return response()->json([
                'message' => 'No se puede eliminar una categoría con exámenes asociados.'
            ], 409);
        }
        $categoria->delete();
        return response()->json(null, 204);
    }
}
