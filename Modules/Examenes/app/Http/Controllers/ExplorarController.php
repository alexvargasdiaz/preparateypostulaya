<?php

declare(strict_types=1);

namespace Modules\Examenes\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Modules\Catalogo\Models\Institucion;
use Modules\Catalogo\Models\Categoria;
use Modules\Catalogo\Models\Examen;

class ExplorarController extends Controller
{
    /**
     * Página principal de exploración: redirige a universidades.
     */
    public function index()
    {
        return redirect()->route('examenes.universidades');
    }

    /**
     * Explora simulacros por universidad: Universidad → Carrera → Examen.
     */
    public function universidades(Request $request)
    {
        $institucionId = $request->query('institucion_id');
        $categoriaId = $request->query('categoria_id');

        // ─── Todas las universidades activas con exámenes disponibles ──
        $instituciones = Institucion::where('activo', true)
            ->whereHas('categorias.examenes', fn ($q) => $q->where('activo', true))
            ->withCount(['categorias' => fn ($q) => $q->whereHas('examenes', fn ($e) => $e->where('activo', true))])
            ->orderBy('nombre')
            ->get()
            ->map(fn ($i) => [
                'id' => $i->id,
                'nombre' => $i->nombre,
                'subtipo' => $i->subtipo,
                'ciudad' => $i->ciudad,
                'categorias_count' => $i->categorias_count,
            ]);

        // ─── Carreras (categorías) de la universidad seleccionada ──
        $categorias = collect();
        if ($institucionId) {
            $categorias = Categoria::where('institucion_id', $institucionId)
                ->whereHas('examenes', fn ($q) => $q->where('activo', true))
                ->withCount(['examenes' => fn ($q) => $q->where('activo', true)])
                ->orderBy('nombre')
                ->get()
                ->map(fn ($c) => [
                    'id' => $c->id,
                    'nombre' => $c->nombre,
                    'descripcion_corta' => $c->descripcion_corta,
                    'examenes_count' => $c->examenes_count,
                ]);
        }

        // ─── Exámenes disponibles para la universidad + carrera ──
        $examenes = collect();
        if ($institucionId && $categoriaId) {
            $examenes = Examen::with('categoria.institucion')
                ->where('categoria_id', $categoriaId)
                ->where('activo', true)
                ->withCount(['preguntasDelExamen as preguntas_count' => fn ($q) => $q->where('activa', true)])
                ->orderBy('titulo')
                ->get()
                ->map(fn ($e) => [
                    'id' => $e->id,
                    'titulo' => $e->titulo,
                    'descripcion' => $e->descripcion,
                    'tiempo_limite_min' => $e->tiempo_limite_min,
                    'preguntas_count' => $e->preguntas_count,
                    'categoria' => $e->categoria?->only(['id', 'nombre']),
                    'institucion' => $e->categoria?->institucion?->only(['id', 'nombre', 'subtipo']),
                ]);
        }

        // ─── Institución y carrera seleccionadas ──────────────
        $institucionSel = $institucionId
            ? Institucion::find($institucionId)?->only(['id', 'nombre', 'subtipo'])
            : null;

        $categoriaSel = $categoriaId
            ? Categoria::with('institucion')->find($categoriaId)
            : null;

        $totalCategorias = Categoria::whereHas('examenes', fn ($q) => $q->where('activo', true))->count();
        $totalExamenes = Examen::where('activo', true)->count();

        return Inertia::render('Examenes/Universidades/Index', [
            'instituciones' => $instituciones,
            'categorias' => $categorias,
            'examenes' => $examenes,
            'totalCategorias' => $totalCategorias,
            'totalExamenes' => $totalExamenes,
            'institucionSel' => $institucionSel,
            'categoriaSel' => $categoriaSel ? [
                'id' => $categoriaSel->id,
                'nombre' => $categoriaSel->nombre,
                'descripcion_corta' => $categoriaSel->descripcion_corta,
            ] : null,
            'filtros' => $request->only(['institucion_id', 'categoria_id']),
        ]);
    }
}
