<?php

declare(strict_types=1);

namespace Modules\Dashboard\Http\Controllers;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Modules\Dashboard\Services\DashboardService;

class DashboardController extends Controller
{
    public function __construct(
        private readonly DashboardService $dashboardService,
    ) {}

    /**
     * Muestra el panel principal del usuario.
     */
    public function index()
    {
        $user = auth()->user();

        if ($user && $user->estaPendiente()) {
            return Inertia::render('Auth/Pendiente');
        }

        $data = [];

        if ($user && $user->guardaHistorial()) {
            $data['ultimosIntentos'] = $this->dashboardService->obtenerUltimosIntentos(auth()->id());
            $data['porInstitucion'] = $this->dashboardService->agruparPorInstitucion(auth()->id());
        }

        return Inertia::render('Dashboard', $data);
    }
}
