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
     * Muestra el historial de simulacros del usuario.
     */
    public function index()
    {
        $intentos = IntentoExamen::with([
            'institucion:id,nombre',
            'examen:id,titulo',
        ])
            ->where('usuario_id', auth()->id())
            ->orderBy('created_at', 'desc')
            ->get();

        return Inertia::render('Historial/Index', [
            'intentos' => $intentos,
        ]);
    }
}
