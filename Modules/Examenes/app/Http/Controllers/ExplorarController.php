<?php

declare(strict_types=1);

namespace Modules\Examenes\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;
use Modules\Catalogo\Models\Institucion;
use Modules\Catalogo\Models\Categoria;
use Modules\Preguntas\Models\AreaAcademica;
use Modules\Preguntas\Models\TipoSimulacro;
use Modules\Rendicion\Models\IntentoExamen;

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
     * Explora simulacros por universidad: Universidad → Área Académica → Tipo de simulacro.
     */
    public function universidades(Request $request)
    {
        $institucionId = $request->query('institucion_id');
        $areaId = $request->query('area_id');

        // Los datos del catálogo (universidades, áreas, tipos, carreras) cambian
        // poco y son iguales para todos los usuarios; se cachean por combinación
        // de filtros para soportar picos de muchos estudiantes explorando a la vez.
        $clave = sprintf(
            'explorar.universidades.%s.%s',
            $institucionId ?? 'todas',
            $areaId ?? 'ninguna',
        );

        $datos = Cache::remember($clave, 600, function () use ($request, $institucionId, $areaId) {
            // ─── Todas las universidades activas ──────────────────────
            $instituciones = Institucion::where('activo', true)
                ->withCount(['categorias' => fn ($q) => $q->where('activo', true)])
                ->orderBy('nombre')
                ->get()
                ->map(fn ($i) => [
                    'id' => $i->id,
                    'nombre' => $i->nombre,
                    'subtipo' => $i->subtipo,
                    'ciudad' => $i->ciudad,
                    'categorias_count' => $i->categorias_count,
                ]);

            // ─── Áreas académicas de la universidad seleccionada ───────
            $areas = collect();
            $institucionSel = $institucionId
                ? Institucion::find($institucionId)
                : null;

            if ($institucionSel) {
                $areas = AreaAcademica::where('activo', true)
                    ->withCount(['preguntas as preguntas_propias' => function ($q) use ($institucionId) {
                        $q->where('activa', true)->where('institucion_id', $institucionId);
                    }])
                    ->withCount(['preguntas as preguntas_globales' => fn ($q) => $q->where('activa', true)->whereNull('institucion_id')])
                    ->withCount(['tiposSimulacroActivos as tipos_count'])
                    ->orderBy('nombre')
                    ->get()
                    ->filter(fn ($a) => $a->preguntas_propias > 0 || $a->preguntas_globales > 0)
                    ->map(fn ($a) => [
                        'id' => $a->id,
                        'nombre' => $a->nombre,
                        'descripcion' => $a->descripcion,
                        // Total de preguntas que se usarían para el simulacro
                        'total_preguntas' => $a->preguntas_propias > 0 ? $a->preguntas_propias : $a->preguntas_globales,
                        'usa_banco_propio' => $a->preguntas_propias > 0,
                        'tipos_count' => $a->tipos_count,
                    ]);
            }

            // ─── Tipos de simulacro disponibles para universidad + área ──
            $tipos = collect();
            if ($institucionSel && $areaId) {
                $tipos = TipoSimulacro::where('area_academica_id', $areaId)
                    ->where('activo', true)
                    ->orderBy('nombre')
                    ->get()
                    ->map(fn ($t) => [
                        'id' => $t->id,
                        'nombre' => $t->nombre,
                        'descripcion' => $t->descripcion,
                        'num_preguntas' => $t->num_preguntas,
                        'duracion_min' => $t->duracion_min,
                    ]);
            }

            // ─── Carreras de la universidad (para el modal de postulación) ──
            // Prioriza las carreras del área académica seleccionada.
            $carreras = collect();
            if ($institucionSel) {
                $carreras = Categoria::where('institucion_id', $institucionId)
                    ->where('activo', true)
                    ->with('areaAcademica:id,nombre')
                    ->orderByRaw('CASE WHEN area_academica_id = ? THEN 0 ELSE 1 END', [$areaId])
                    ->orderBy('nombre')
                    ->get()
                    ->map(fn ($c) => [
                        'id' => $c->id,
                        'nombre' => $c->nombre,
                        'area_academica_id' => $c->area_academica_id,
                        'area_nombre' => $c->areaAcademica?->nombre,
                    ]);
            }

            return [
                'instituciones' => $instituciones,
                'areas' => $areas,
                'tipos' => $tipos,
                'carreras' => $carreras,
                'institucionSel' => $institucionSel?->only(['id', 'nombre', 'subtipo']),
                'areaSel' => $areaId
                    ? AreaAcademica::find($areaId)?->only(['id', 'nombre'])
                    : null,
            ];
        });

        // ─── Intento activo del alumno (para continuar) ──────────
        // Se calcula por usuario, fuera de la caché del catálogo.
        $intentoActivo = null;
        if ($institucionId && $areaId && auth()->check()) {
            $intentoActivo = IntentoExamen::where('usuario_id', auth()->id())
                ->where('institucion_id', $institucionId)
                ->where('area_academica_id', $areaId)
                ->where('estado', 'en_curso')
                ->first();
        }

        return Inertia::render('Examenes/Universidades/Index', [
            'instituciones' => $datos['instituciones'],
            'areas' => $datos['areas'],
            'tipos' => $datos['tipos'],
            'carreras' => $datos['carreras'],
            'institucionSel' => $datos['institucionSel'],
            'areaSel' => $datos['areaSel'],
            'intentoActivo' => $intentoActivo,
            'filtros' => $request->only(['institucion_id', 'area_id']),
        ]);
    }
}
