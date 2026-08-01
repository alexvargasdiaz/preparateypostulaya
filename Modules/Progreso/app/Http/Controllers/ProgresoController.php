<?php

declare(strict_types=1);

namespace Modules\Progreso\Http\Controllers;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Modules\Progreso\Services\ProgresoService;

class ProgresoController extends Controller
{
    public function __construct(
        private readonly ProgresoService $progresoService,
    ) {}

    /**
     * Muestra la página de progreso y evolución del usuario.
     */
    public function index()
    {
        $userId = auth()->id();

        $stats = $this->progresoService->obtenerEstadisticas($userId);
        $evolucion = $this->progresoService->obtenerEvolucion($userId);
        $rendimientoConceptos = $this->progresoService->obtenerRendimientoConceptos($userId);
        $recientes = $this->progresoService->obtenerRecientes($userId);

        return Inertia::render('Progreso/Index', [
            'stats' => $stats,
            'evolucion' => $evolucion,
            'rendimiento_conceptos' => $rendimientoConceptos,
            'recientes' => $recientes,
            'tiene_datos' => $stats['total_examenes'] > 0,
        ]);
    }
}
