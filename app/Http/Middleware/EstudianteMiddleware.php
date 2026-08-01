<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EstudianteMiddleware
{
    /**
     * Handle an incoming request.
     * Permite el paso solo a usuarios con rol Estudiante.
     * Admins, super admins e invitados no pueden acceder.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user || !$user->guardaHistorial()) {
            abort(403, 'Acceso denegado. Esta sección es solo para estudiantes.');
        }

        return $next($request);
    }
}
