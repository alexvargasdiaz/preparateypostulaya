<?php

declare(strict_types=1);

namespace Modules\Notificaciones\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Notificaciones\Models\Notificacion;
use Modules\Notificaciones\Models\PreferenciaNotificacion;
use Modules\Notificaciones\Services\NotificationService;

class NotificacionController extends Controller
{
    public function __construct(
        private readonly NotificationService $notificationService,
    ) {}

    /**
     * Obtiene la audiencia del usuario autenticado.
     */
    private function obtenerAudiencia(): string
    {
        $rol = auth()->user()->rol;
        return $rol?->esAdmin() ? 'admin' : 'alumno';
    }

    /**
     * Muestra la página de notificaciones del usuario.
     */
    public function index(): Response
    {
        $usuarioId = auth()->id();
        $audiencia = $this->obtenerAudiencia();

        $this->notificationService->marcarTodasLeidas($usuarioId, $audiencia);

        $notificaciones = Notificacion::where('usuario_id', $usuarioId)
            ->where(function ($query) use ($audiencia) {
                $query->where('audiencia', $audiencia)
                      ->orWhere('audiencia', 'all');
            })
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        $preferencias = PreferenciaNotificacion::paraUsuario($usuarioId);

        return Inertia::render('Notificaciones/Index', [
            'notificaciones' => $notificaciones,
            'preferencias' => $preferencias,
            'noLeidas' => 0,
            'audiencia' => $audiencia,
        ]);
    }

    /**
     * API: Obtiene notificaciones recientes (para el dropdown del navbar).
     */
    public function obtenerRecientes(): JsonResponse
    {
        $usuarioId = auth()->id();
        $audiencia = $this->obtenerAudiencia();

        return response()->json([
            'notificaciones' => $this->notificationService->obtenerRecientes($usuarioId, 5, $audiencia),
            'noLeidas' => $this->notificationService->contarNoLeidas($usuarioId, $audiencia),
            'audiencia' => $audiencia,
        ]);
    }

    /**
     * API: Marca una notificación como leída.
     */
    public function marcarLeida(int $id): JsonResponse
    {
        $result = $this->notificationService->marcarLeida($id, auth()->id());

        if (!$result) {
            return response()->json(['error' => 'Notificación no encontrada'], 404);
        }

        return response()->json(['success' => true]);
    }

    /**
     * API: Marca todas las notificaciones como leídas.
     */
    public function marcarTodasLeidas(): JsonResponse
    {
        $audiencia = $this->obtenerAudiencia();
        $count = $this->notificationService->marcarTodasLeidas(auth()->id(), $audiencia);

        return response()->json([
            'success' => true,
            'marcadas' => $count,
        ]);
    }

    /**
     * Actualiza las preferencias de notificación.
     */
    public function actualizarPreferencias(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email_resultados' => 'boolean',
            'whatsapp_resultados' => 'boolean',
            'recordatorio_estudio' => 'boolean',
            'novedades' => 'boolean',
        ]);

        $prefs = PreferenciaNotificacion::paraUsuario(auth()->id());
        $prefs->update($validated);

        return response()->json([
            'success' => true,
            'preferencias' => $prefs->fresh(),
        ]);
    }
}
