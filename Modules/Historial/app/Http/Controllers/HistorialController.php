<?php

declare(strict_types=1);

namespace Modules\Historial\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Modules\Rendicion\Models\IntentoExamen;

class HistorialController extends Controller
{
    /**
     * Muestra el historial de simulacros del usuario (paginado).
     */
    public function index()
    {
        $intentos = IntentoExamen::with([
            'institucion:id,nombre',
            'examen:id,titulo',
        ])
            ->where('usuario_id', auth()->id())
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return Inertia::render('Historial/Index', [
            'intentos' => $intentos->items(),
            'paginacion' => [
                'pagina' => $intentos->currentPage(),
                'ultimaPagina' => $intentos->lastPage(),
                'paginaAnterior' => $intentos->previousPageUrl(),
                'paginaSiguiente' => $intentos->nextPageUrl(),
            ],
        ]);
    }
}
