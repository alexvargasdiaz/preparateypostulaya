<?php

declare(strict_types=1);

namespace Modules\Catalogo\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Catalogo\Models\Examen;

class ExamenController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $examenes = Examen::with('categoria.institucion')
            ->withCount('secciones')
            ->orderBy('created_at', 'desc')
            ->paginate($request->integer('per_page', 50));
        return response()->json($examenes);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'categoria_id' => 'required|exists:categorias,id',
            'titulo' => 'required|string|max:200',
            'descripcion' => 'nullable|string',
            'imagen_url' => 'nullable|url|max:500',
            'tiempo_limite_min' => 'required|integer|min:1|max:480',
            'intentos_permitidos' => 'integer|min:1|max:99',
            'puntaje_minimo' => 'nullable|numeric|min:0',
            'num_alternativas_default' => 'integer|min:2|max:10',
            'aleatorizar_preguntas' => 'boolean',
            'aleatorizar_alternativas' => 'boolean',
            'activo' => 'boolean',
        ]);

        $examen = Examen::create($validated);
        return response()->json($examen, 201);
    }

    public function show($id): JsonResponse
    {
        $examen = Examen::with([
            'categoria.institucion.tipoExamen',
            'secciones.conceptos',
            'secciones' => function ($q) {
                $q->orderBy('orden');
            },
        ])->findOrFail($id);
        return response()->json($examen);
    }

    public function update(Request $request, $id): JsonResponse
    {
        $examen = Examen::findOrFail($id);
        $validated = $request->validate([
            'categoria_id' => 'required|exists:categorias,id',
            'titulo' => 'required|string|max:200',
            'descripcion' => 'nullable|string',
            'imagen_url' => 'nullable|url|max:500',
            'tiempo_limite_min' => 'required|integer|min:1|max:480',
            'intentos_permitidos' => 'integer|min:1|max:99',
            'puntaje_minimo' => 'nullable|numeric|min:0',
            'num_alternativas_default' => 'integer|min:2|max:10',
            'aleatorizar_preguntas' => 'boolean',
            'aleatorizar_alternativas' => 'boolean',
            'activo' => 'boolean',
        ]);

        $examen->update($validated);
        return response()->json($examen);
    }

    public function destroy($id): JsonResponse
    {
        $examen = Examen::findOrFail($id);
        if ($examen->secciones()->exists()) {
            return response()->json([
                'message' => 'Elimina primero las secciones asociadas a este examen.'
            ], 409);
        }
        $examen->delete();
        return response()->json(null, 204);
    }
}
